<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos;

use ExeQue\Qr\Exceptions\InvalidLogoException;
use ExeQue\Qr\Logos\Contracts\SvgSanitizer;
use ExeQue\Qr\Writers\Raster\Enums\Driver;
use Imagick;
use ImagickException;

/**
 * An image to place over a code: PNG, JPEG, WebP, GIF (its first frame) or SVG.
 * An SVG is sanitized when the logo is made, by the sanitizer given or the one
 * bound in the container, so what it holds is always safe to inline.
 */
final readonly class Logo
{
    // The brands an ISO media file names in its ftyp box, by what it holds.
    private const array HEIF_BRANDS = ['heic', 'heix', 'hevc', 'hevx', 'heim', 'heis', 'mif1', 'msf1'];

    private const array AVIF_BRANDS = ['avif', 'avis'];

    /**
     * The most pixels a raster logo may have: plenty for any code, too few to exhaust memory.
     */
    public const int MAX_PIXELS = 16_777_216;

    /**
     * @param string $hash Of the contents: what caching goes by.
     */
    private function __construct(
        public string $contents,
        public string $mimeType,
        public float $width,
        public float $height,
        public string $hash,
    ) {
    }

    /**
     * An image of any supported type, told apart by its first bytes.
     */
    public static function fromBytes(string $bytes, ?SvgSanitizer $sanitizer = null): self
    {
        if (preg_match('/^\s*(<\?xml[^>]*>\s*)?(<!--.*?-->\s*)*<svg[\s>]/s', $bytes) === 1) {
            return self::fromSvg($bytes, $sanitizer);
        }

        $mimeType = match (true) {
            str_starts_with($bytes, "\x89PNG\r\n\x1A\n") => 'image/png',
            str_starts_with($bytes, "\xFF\xD8\xFF") => 'image/jpeg',
            str_starts_with($bytes, 'GIF8') => 'image/gif',
            str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP' => 'image/webp',
            substr($bytes, 4, 4) === 'ftyp' && in_array(substr($bytes, 8, 4), self::AVIF_BRANDS, true) => 'image/avif',
            substr($bytes, 4, 4) === 'ftyp' && in_array(substr($bytes, 8, 4), self::HEIF_BRANDS, true) => 'image/heif',
            default => throw InvalidLogoException::unsupportedType(),
        };

        self::checkPixels($bytes, $mimeType);

        // A GIF's first frame, and HEIF and AVIF, which browsers and GD read only in part, become PNG.
        [$bytes, $mimeType] = match ($mimeType) {
            'image/gif' => [self::firstFrame($bytes), 'image/png'],
            'image/heif' => [self::toPng($bytes, 'HEIC'), 'image/png'],
            'image/avif' => [self::toPng($bytes, 'AVIF'), 'image/png'],
            default => [$bytes, $mimeType],
        };

        $size = getimagesizefromstring($bytes) ?: throw InvalidLogoException::unreadable();

        return new self($bytes, $mimeType, (float) $size[0], (float) $size[1], hash('xxh128', $bytes));
    }

    public static function fromFile(string $path, ?SvgSanitizer $sanitizer = null): self
    {
        // A stream wrapper (ftp://, http://, phar://...) would fetch from elsewhere or run code.
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path) === 1) {
            throw InvalidLogoException::notLocal($path);
        }

        $bytes = is_file($path) && is_readable($path) ? file_get_contents($path) : false;

        return self::fromBytes($bytes === false ? throw InvalidLogoException::missingFile($path) : $bytes, $sanitizer);
    }

    /**
     * A `data:` URI of an image, base64 or not.
     */
    public static function fromDataUri(string $uri, ?SvgSanitizer $sanitizer = null): self
    {
        if (preg_match('/^data:image\/[\w.+-]+(;[\w=-]+)*?(;base64)?,(.*)$/s', $uri, $match) !== 1) {
            throw InvalidLogoException::notADataUri();
        }

        $bytes = $match[2] === ';base64' ? base64_decode($match[3], true) : rawurldecode($match[3]);

        return self::fromBytes($bytes === false ? throw InvalidLogoException::notADataUri() : $bytes, $sanitizer);
    }

    public static function fromSvg(string $svg, ?SvgSanitizer $sanitizer = null): self
    {
        $clean = ($sanitizer ?? app(SvgSanitizer::class))->clean($svg);
        [$width, $height] = self::svgSize($clean);

        return new self($clean, 'image/svg+xml', $width, $height, hash('xxh128', $clean));
    }

    public function isSvg(): bool
    {
        return $this->mimeType === 'image/svg+xml';
    }

    /**
     * Width over height.
     */
    public function aspectRatio(): float
    {
        return $this->width / $this->height;
    }

    public function toDataUri(): string
    {
        return "data:{$this->mimeType};base64,".base64_encode($this->contents);
    }

    /**
     * Refuses an image with more pixels than a logo may have, from its header, before it is decoded.
     */
    private static function checkPixels(string $bytes, string $mimeType): void
    {
        $size = match ($mimeType) {
            'image/heif', 'image/avif' => self::pinged($bytes),
            default => getimagesizefromstring($bytes),
        };

        if ($size === false) {
            throw InvalidLogoException::unreadable();
        }

        if ($size[0] * $size[1] > self::MAX_PIXELS) {
            throw InvalidLogoException::tooLarge($size[0], $size[1], self::MAX_PIXELS);
        }

        // A GIF's header gives its canvas, which its frames may be larger than; and reading it decodes
        // every frame, so they count together.
        $frames = $mimeType === 'image/gif' ? self::frames($bytes) : [];
        $pixels = array_sum(array_map(fn (array $frame): int => $frame[0] * $frame[1], $frames));

        if ($pixels > self::MAX_PIXELS) {
            throw InvalidLogoException::tooManyPixels($pixels, self::MAX_PIXELS);
        }
    }

    /**
     * The size of each of a GIF's frames, from ImageMagick reading their headers only; none without Imagick.
     *
     * @return list<array{int, int}>
     */
    private static function frames(string $gif): array
    {
        if (! extension_loaded('imagick')) {
            return []; // @codeCoverageIgnore
        }

        try {
            $image = new Imagick();
            $image->pingImageBlob($gif);
        } catch (ImagickException) {
            return [];
        }

        $sizes = [];

        foreach ($image as $frame) {
            $sizes[] = [$frame->getImageWidth(), $frame->getImageHeight()];
        }

        return $sizes;
    }

    /**
     * A HEIF or AVIF image's size, from ImageMagick reading its header only.
     *
     * @return array{int, int}|false
     */
    private static function pinged(string $bytes): array|false
    {
        if (! extension_loaded('imagick')) {
            return false; // @codeCoverageIgnore
        }

        try {
            $image = new Imagick();
            $image->pingImageBlob($bytes);

            return [$image->getImageWidth(), $image->getImageHeight()];
        } catch (ImagickException) {
            return false;
        }
    }

    /**
     * The size an SVG says it has: its viewBox, else its width and height, else a square.
     *
     * @return array{float, float}
     */
    private static function svgSize(string $svg): array
    {
        // Numbers as SVG writes them: 10, -2.5, .5, 2e2.
        $number = '-?[\d.]+(?:e[-+]?\d+)?';

        if (preg_match("/<svg[^>]*\\sviewBox=\"\\s*{$number}[\\s,]+{$number}[\\s,]+({$number})[\\s,]+({$number})\\s*\"/i", $svg, $match) === 1 && (float) $match[1] > 0 && (float) $match[2] > 0) {
            return [(float) $match[1], (float) $match[2]];
        }

        $width = preg_match('/<svg[^>]*\swidth="([\d.]+)/i', $svg, $w) === 1 ? (float) $w[1] : 0.0;
        $height = preg_match('/<svg[^>]*\sheight="([\d.]+)/i', $svg, $h) === 1 ? (float) $h[1] : 0.0;

        return $width > 0 && $height > 0 ? [$width, $height] : [1.0, 1.0];
    }

    /**
     * A GIF's first frame, as PNG.
     */
    /**
     * A HEIF or AVIF image as PNG, through ImageMagick's support for it.
     */
    private static function toPng(string $bytes, string $format): string
    {
        if (! extension_loaded('imagick') || Imagick::queryFormats($format) === []) {
            throw InvalidLogoException::heifNeedsImageMagick($format === 'AVIF' ? 'AVIF' : 'HEIF'); // @codeCoverageIgnore
        }

        try {
            $image = new Imagick();
            $image->readImageBlob($bytes);
            $image->setImageFormat('png');
            $image->setOption('png:exclude-chunks', 'date,time');

            return $image->getImageBlob();
        } catch (ImagickException) {
            throw InvalidLogoException::unreadable();
        }
    }

    private static function firstFrame(string $gif): string
    {
        if (Driver::resolve() === Driver::Gd) {
            // GD reads a GIF's first frame only.
            $image = imagecreatefromstring($gif) ?: throw InvalidLogoException::unreadable();
            imagesavealpha($image, true);
            ob_start();
            imagepng($image);

            return (string) ob_get_clean();
        }

        try {
            $image = new Imagick();
            $image->readImageBlob($gif);
            $image->setIteratorIndex(0);

            $frame = $image->getImage();
            $frame->setImageFormat('png');
            $frame->setOption('png:exclude-chunks', 'date,time');

            return $frame->getImageBlob();
        } catch (ImagickException) {
            throw InvalidLogoException::unreadable();
        }
    }
}
