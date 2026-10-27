<?php

declare(strict_types=1);

namespace ExeQue\Qr\Decoding\Native;

use ExeQue\Qr\Exceptions\UnreadableCodeException;
use ExeQue\Qr\Writers\Raster\Enums\Driver;
use Imagick;
use ImagickException;
use ImagickPixel;

/**
 * An image as gray levels, and as dark and light pixels.
 *
 * @internal
 */
final readonly class Bitmap
{
    // Larger images are scaled down first: finding the code needs no more detail than this.
    private const int MAX_SIDE = 800;

    // What an image may have before it is decoded at all: enough for a photo, not for a bomb.
    private const int MAX_PIXELS = 50_000_000;

    /**
     * @param list<int> $gray 0 (black) to 255 (white), row by row.
     */
    public function __construct(
        public int $width,
        public int $height,
        public array $gray,
    ) {
    }

    public static function fromImage(string $bytes, ?Driver $driver = null): self
    {
        // Only the raster formats codes come in: ImageMagick reads many more, some of them unsafe.
        if (! self::isRaster($bytes)) {
            throw UnreadableCodeException::unreadableImage();
        }

        if (Driver::resolve($driver) === Driver::Gd) {
            return self::withGd($bytes);
        }

        try {
            $image = new Imagick();
            $image->pingImageBlob($bytes);
            $pixels = 0;

            // Reading decodes every frame of an animation, so they count together.
            foreach ($image as $frame) {
                $pixels += $frame->getImageWidth() * $frame->getImageHeight();
            }

            if ($pixels > self::MAX_PIXELS) {
                throw UnreadableCodeException::unreadableImage();
            }

            $image = new Imagick();
            $image->readImageBlob($bytes);
            $image->setIteratorIndex(0);
            $image->setImageBackgroundColor(new ImagickPixel('white'));
            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_REMOVE);

            if (max($image->getImageWidth(), $image->getImageHeight()) > self::MAX_SIDE) {
                $image->thumbnailImage(self::MAX_SIDE, self::MAX_SIDE, true);
            }

            [$width, $height] = [$image->getImageWidth(), $image->getImageHeight()];
            $gray = $image->exportImagePixels(0, 0, $width, $height, 'I', Imagick::PIXEL_CHAR);
            $image->clear();
        } catch (ImagickException) {
            throw UnreadableCodeException::unreadableImage();
        }

        return new self($width, $height, $gray);
    }

    /**
     * The image read with GD, for servers without Imagick.
     */
    private static function withGd(string $bytes): self
    {
        $size = @getimagesizefromstring($bytes);

        if ($size === false || $size[0] * $size[1] > self::MAX_PIXELS) {
            throw UnreadableCodeException::unreadableImage();
        }

        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            throw UnreadableCodeException::unreadableImage(); // @codeCoverageIgnore
        }

        // On white, so transparent parts read as light.
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($flat, 0, 0, 0xFFFFFF);
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        if (max(imagesx($flat), imagesy($flat)) > self::MAX_SIDE) {
            $ratio = self::MAX_SIDE / max(imagesx($flat), imagesy($flat));
            $flat = imagescale($flat, max(1, (int) round(imagesx($flat) * $ratio)), max(1, (int) round(imagesy($flat) * $ratio)), IMG_BICUBIC) ?: $flat;
        }

        [$width, $height] = [imagesx($flat), imagesy($flat)];
        $gray = [];

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($flat, $x, $y);
                $gray[] = (int) round(0.2126 * (($color >> 16) & 0xFF) + 0.7152 * (($color >> 8) & 0xFF) + 0.0722 * ($color & 0xFF));
            }
        }

        return new self($width, $height, $gray);
    }

    /**
     * PNG, JPEG, WebP or GIF, by their first bytes.
     */
    /**
     * The image as a binary PGM, gray levels as they are: a format every image library reads.
     */
    public function toPgm(): string
    {
        return "P5\n{$this->width} {$this->height}\n255\n".pack('C*', ...$this->gray);
    }

    private static function isRaster(string $bytes): bool
    {
        return str_starts_with($bytes, "\x89PNG\r\n\x1A\n")
            || str_starts_with($bytes, "\xFF\xD8\xFF")
            || str_starts_with($bytes, 'GIF8')
            || (str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP');
    }

    /**
     * Dark where darker than a threshold for the whole image, picked by Otsu's method: the one
     * that separates the gray levels into the two most distinct groups.
     *
     * @return list<bool>
     */
    public function global(): array
    {
        $histogram = array_fill(0, 256, 0);

        foreach ($this->gray as $level) {
            $histogram[$level]++;
        }

        $total = count($this->gray);
        $sum = 0;

        foreach ($histogram as $level => $count) {
            $sum += $level * $count;
        }

        [$best, $threshold, $below, $sumBelow] = [-1.0, 127, 0, 0];

        foreach ($histogram as $level => $count) {
            $below += $count;
            $sumBelow += $level * $count;
            $above = $total - $below;

            if ($below === 0 || $above === 0) {
                continue;
            }

            $difference = $sumBelow / $below - ($sum - $sumBelow) / $above;
            $between = $below * $above * $difference ** 2;

            if ($between > $best) {
                [$best, $threshold] = [$between, $level];
            }
        }

        return array_map(fn (int $level): bool => $level <= $threshold, $this->gray);
    }

    /**
     * Dark where darker than the pixels around it, for uneven light and gradients.
     *
     * @return list<bool>
     */
    public function local(): array
    {
        [$width, $height] = [$this->width, $this->height];
        $radius = max(4, intdiv(min($width, $height), 16));

        // A summed-area table, one wider and taller, so any box's sum takes four lookups.
        $sums = array_fill(0, ($width + 1) * ($height + 1), 0);

        for ($y = 0; $y < $height; $y++) {
            $row = 0;

            for ($x = 0; $x < $width; $x++) {
                $row += $this->gray[$y * $width + $x];
                $sums[($y + 1) * ($width + 1) + $x + 1] = $sums[$y * ($width + 1) + $x + 1] + $row;
            }
        }

        $dark = [];

        for ($y = 0; $y < $height; $y++) {
            [$top, $bottom] = [max(0, $y - $radius), min($height, $y + $radius + 1)];

            for ($x = 0; $x < $width; $x++) {
                [$left, $right] = [max(0, $x - $radius), min($width, $x + $radius + 1)];

                $sum = $sums[$bottom * ($width + 1) + $right] - $sums[$top * ($width + 1) + $right]
                    - $sums[$bottom * ($width + 1) + $left] + $sums[$top * ($width + 1) + $left];
                $mean = $sum / (($bottom - $top) * ($right - $left));

                $dark[] = $this->gray[$y * $width + $x] < $mean - 8;
            }
        }

        return $dark;
    }
}
