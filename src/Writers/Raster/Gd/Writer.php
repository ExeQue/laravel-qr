<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster\Gd;

use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidWriterOptionException;
use ExeQue\Qr\Exceptions\MissingExtensionException;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Rendering\LogoLayer;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\SceneItem;
use ExeQue\Qr\Writers\Boxes;
use ExeQue\Qr\Writers\Contracts\Fakeable;
use ExeQue\Qr\Writers\Contracts\Writer as WriterContract;
use ExeQue\Qr\Writers\Raster\Concerns\GroupsItems;
use ExeQue\Qr\Writers\Raster\Coverage;
use ExeQue\Qr\Writers\Raster\Enums\ImageFormat;
use ExeQue\Qr\Writers\Raster\Image;
use ExeQue\Qr\Writers\Raster\Options;
use ExeQue\Qr\Writers\StreamBuffer;
use ExeQue\Qr\Writers\StreamFactory;
use GdImage;

/**
 * Draws a scene as a PNG, JPEG or WebP with GD, for servers without Imagick: the shapes
 * are measured into pixel coverage by `Coverage`, so they come out smooth and filled with
 * the nonzero rule, as with Imagick.
 *
 * GD cannot read SVG, so SVG logos need Imagick. Metadata is written into PNG and JPEG.
 */
final readonly class Writer implements Fakeable, WriterContract
{
    use GroupsItems;

    // The most samples a gradient takes along each side; scaled up smoothly, more is not visible.
    private const int GRADIENT_SAMPLES = 128;

    public function __construct(
        private Options $options = new Options(),
        private StreamFactory $streams = new StreamFactory(),
    ) {
    }

    public function write(Scene $scene): Image
    {
        if (! extension_loaded('gd')) {
            throw MissingExtensionException::gd(); // @codeCoverageIgnore
        }

        $size = $this->options->size;
        $scale = $size / $scene->size;
        $canvas = self::blank($size, $size);

        if ($scene->background !== null) {
            $this->fill($canvas, $scene->background, null, 1.0, $scene, null, $scale);
        }

        foreach ($this->groups($scene) as $group) {
            $this->fill($canvas, $group['paint'], $group['paths'], $group['opacity'], $scene, $group['item'], $scale);
        }

        if ($scene->logo !== null) {
            $this->logo($canvas, $scene->logo, $scene, $scale);
        }

        return new Image($this->encode($canvas), $this->options->format);
    }

    /**
     * A ready-made code reading "qr-fake", 256 pixels wide, in the format asked for; no GD needed.
     */
    public function fake(Scene $scene): Image
    {
        $file = dirname(__DIR__, 4).'/resources/fakes/qr.'.$this->options->format->extension();

        return new Image($this->streams->buffer()->write((string) file_get_contents($file)), $this->options->format);
    }

    /**
     * @param list<Path>|null $paths Null for the whole canvas.
     * @param SceneItem|Path|null $shape The first of the items painted, or the logo's plate; null for the background.
     */
    private function fill(GdImage $canvas, Paint $paint, ?array $paths, float $opacity, Scene $scene, SceneItem|Path|null $shape, float $scale): void
    {
        if ($paint instanceof Layered) {
            foreach ($paint->layers as $layer) {
                $this->fill($canvas, $layer->paint, $paths, $opacity * $layer->opacity, $scene, $shape, $scale);
            }

            return;
        }

        [$width, $height] = [imagesx($canvas), imagesy($canvas)];
        $coverage = $paths === null
            ? array_fill(0, $height, [0, array_fill(0, $width, 1.0)])
            : Coverage::of($paths, $scale, $width, $height);

        if ($paint instanceof ColorContract) {
            $rgba = $paint->toRgba();
            $rgb = ((int) round($rgba->red) << 16) | ((int) round($rgba->green) << 8) | (int) round($rgba->blue);
            $solid = $rgba->alpha * $opacity >= 1;

            foreach ($coverage as $row => [$from, $values]) {
                $run = null;

                // Fully covered stretches are drawn as one line; the edges pixel by pixel.
                foreach ($values as $index => $amount) {
                    if ($solid && $amount >= 1) {
                        $run ??= $from + $index;

                        if (($values[$index + 1] ?? 0.0) >= 1) {
                            continue;
                        }

                        imagefilledrectangle($canvas, $run, $row, $from + $index, $row, $rgb);
                        $run = null;

                        continue;
                    }

                    self::blend($canvas, $from + $index, $row, $rgb, $rgba->alpha * $opacity * $amount);
                }
            }

            return;
        }

        if (! $paint instanceof Gradient) {
            throw InvalidWriterOptionException::unsupportedPaint($paint);
        }

        // The gradient, as large as its box, read where the shapes are.
        [$x, $y, $width, $height] = array_map(fn (float $value): float => $value * $scale, Boxes::on($paint, $scene, $shape));
        [$left, $top] = [(int) round($x), (int) round($y)];
        $sample = $this->sample($paint, (int) max(1, round($width)), (int) max(1, round($height)));
        [$right, $bottom] = [imagesx($sample) - 1, imagesy($sample) - 1];

        foreach ($coverage as $row => [$from, $values]) {
            foreach ($values as $index => $amount) {
                $column = $from + $index;
                $color = imagecolorat($sample, max(0, min($right, $column - $left)), max(0, min($bottom, $row - $top)));
                $alpha = (127 - (($color >> 24) & 0x7F)) / 127;

                self::blend($canvas, $column, $row, $color & 0xFFFFFF, $alpha * $opacity * $amount);
            }
        }
    }

    /**
     * The logo's plate, then the image, fitted into its box keeping its proportions and cut to its crop.
     */
    private function logo(GdImage $canvas, LogoLayer $layer, Scene $scene, float $scale): void
    {
        $style = $layer->style;

        if ($style->plate !== null) {
            $this->fill($canvas, $style->plate, [$layer->area], 1.0, $scene, $layer->area, $scale);
        }

        if ($style->logo->isSvg()) {
            throw MissingExtensionException::svgLogosWithGd();
        }

        $image = imagecreatefromstring($style->logo->contents);

        if ($image === false) {
            throw MissingExtensionException::svgLogosWithGd(); // @codeCoverageIgnore
        }

        [$x, $y, $width, $height] = array_map(fn (float $value): float => $value * $scale, $layer->box);
        $fit = min($width / imagesx($image), $height / imagesy($image));
        [$imageWidth, $imageHeight] = [(int) max(1, round(imagesx($image) * $fit)), (int) max(1, round(imagesy($image) * $fit))];
        [$left, $top] = [(int) round($x + ($width - $imageWidth) / 2), (int) round($y + ($height - $imageHeight) / 2)];

        $fitted = self::blank($imageWidth, $imageHeight);
        imagealphablending($fitted, false);
        imagecopyresampled($fitted, $image, 0, 0, 0, 0, $imageWidth, $imageHeight, imagesx($image), imagesy($image));

        $crop = $layer->crop === null ? null : Coverage::of([$layer->crop], $scale, imagesx($canvas), imagesy($canvas));

        for ($row = 0; $row < $imageHeight; $row++) {
            for ($column = 0; $column < $imageWidth; $column++) {
                [$canvasColumn, $canvasRow] = [$left + $column, $top + $row];
                $color = imagecolorat($fitted, $column, $row);
                $alpha = (127 - (($color >> 24) & 0x7F)) / 127;
                $kept = $crop === null ? 1.0 : Coverage::at($crop, $canvasColumn, $canvasRow);

                self::blend($canvas, $canvasColumn, $canvasRow, $color & 0xFFFFFF, $alpha * $kept);
            }
        }
    }

    /**
     * The gradient on a coarse grid, scaled up smoothly to `$width` by `$height`.
     */
    private function sample(Gradient $gradient, int $width, int $height): GdImage
    {
        [$columns, $rows] = [max(2, min(self::GRADIENT_SAMPLES, $width)), max(2, min(self::GRADIENT_SAMPLES, $height))];
        $grid = self::blank($columns, $rows);
        imagealphablending($grid, false);

        for ($row = 0; $row < $rows; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                $rgba = $gradient->colorAt(($column + 0.5) / $columns, ($row + 0.5) / $rows)->toRgba();
                $alpha = 127 - (int) round($rgba->alpha * 127);
                imagesetpixel($grid, $column, $row, ($alpha << 24) | ((int) round($rgba->red) << 16) | ((int) round($rgba->green) << 8) | (int) round($rgba->blue));
            }
        }

        $scaled = self::blank($width, $height);
        imagealphablending($scaled, false);
        imagecopyresampled($scaled, $grid, 0, 0, 0, 0, $width, $height, $columns, $rows);

        return $scaled;
    }

    /**
     * Lays a color over a pixel, `$amount` of it showing.
     */
    private static function blend(GdImage $canvas, int $column, int $row, int $rgb, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        imagesetpixel($canvas, $column, $row, ((127 - (int) round(min(1.0, $amount) * 127)) << 24) | $rgb);
    }

    /**
     * A transparent true-color image that blends what is drawn on it and keeps its alpha.
     */
    private static function blank(int $width, int $height): GdImage
    {
        $image = imagecreatetruecolor(max(1, $width), max(1, $height));
        imagealphablending($image, false);
        imagefill($image, 0, 0, 0x7F000000);
        imagesavealpha($image, true);
        imagealphablending($image, true);

        return $image;
    }

    private function encode(GdImage $canvas): StreamBuffer
    {
        $format = $this->options->format;

        if (! $format->hasTransparency()) {
            $flat = imagecreatetruecolor(imagesx($canvas), imagesy($canvas));
            imagefill($flat, 0, 0, 0xFFFFFF);
            imagecopy($flat, $canvas, 0, 0, 0, 0, imagesx($canvas), imagesy($canvas));
            $canvas = $flat;
        }

        if ($this->options->dpi !== null) {
            imageresolution($canvas, $this->options->dpi, $this->options->dpi);
        }

        ob_start();

        match ($format) {
            ImageFormat::Png => imagepng($canvas, null, $this->options->compressionLevel),
            ImageFormat::Jpeg => imagejpeg($canvas, null, $this->options->quality),
            ImageFormat::Webp => imagewebp($canvas, null, $this->options->quality),
        };

        return $this->streams->buffer()->write($this->withMetadata((string) ob_get_clean()));
    }

    /**
     * The metadata written into the file: PNG text chunks and XMP, or XMP in a JPEG.
     * WebP keeps metadata in a header GD does not write, so a WebP carries none.
     */
    private function withMetadata(string $bytes): string
    {
        $metadata = $this->options->metadata;

        if ($metadata->isEmpty()) {
            return $bytes;
        }

        return match ($this->options->format) {
            ImageFormat::Png => Metadata::intoPng($bytes, $metadata),
            ImageFormat::Jpeg => Metadata::intoJpeg($bytes, $metadata),
            ImageFormat::Webp => $bytes,
        };
    }
}
