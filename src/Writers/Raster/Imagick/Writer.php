<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster\Imagick;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidWriterOptionException;
use ExeQue\Qr\Exceptions\MissingExtensionException;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Logos\Logo;
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
use Imagick;
use ImagickPixel;

/**
 * Draws a scene as a PNG, JPEG or WebP with Imagick: the shapes are measured into pixel
 * coverage by `Coverage`, the same as with GD, and ImageMagick lays the paints over them.
 *
 * Gradients are sampled on a coarse grid, scaled up smoothly and laid over the
 * shapes; gradients sampled per module give each shape the color at its middle.
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
        if (! extension_loaded('imagick')) {
            throw MissingExtensionException::imagick(); // @codeCoverageIgnore
        }

        $size = $this->options->size;
        $scale = $size / $scene->size;

        $canvas = new Imagick();
        $canvas->newImage($size, $size, new ImagickPixel('transparent'));

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
     * A ready-made code reading "qr-fake", 256 pixels wide, in the format asked for; no Imagick needed.
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
    private function fill(Imagick $canvas, Paint $paint, ?array $paths, float $opacity, Scene $scene, SceneItem|Path|null $shape, float $scale): void
    {
        if ($paint instanceof ColorContract) {
            $rgba = $paint->toRgba();
            $layer = new Imagick();
            $layer->newImage($canvas->getImageWidth(), $canvas->getImageHeight(), new ImagickPixel(sprintf('rgba(%d,%d,%d,%F)', round($rgba->red), round($rgba->green), round($rgba->blue), $rgba->alpha * $opacity)));
            $layer->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);

            if ($paths !== null) {
                $layer->compositeImage($this->mask($canvas, $paths, $scale), Imagick::COMPOSITE_DSTIN, 0, 0);
            }

            $canvas->compositeImage($layer, Imagick::COMPOSITE_OVER, 0, 0);

            return;
        }

        if ($paint instanceof Layered) {
            foreach ($paint->layers as $layer) {
                $this->fill($canvas, $layer->paint, $paths, $opacity * $layer->opacity, $scene, $shape, $scale);
            }

            return;
        }

        if (! $paint instanceof Gradient) {
            throw InvalidWriterOptionException::unsupportedPaint($paint);
        }

        // The gradient, as large as its box, kept only where the shapes are.
        $layer = new Imagick();
        $layer->newImage($canvas->getImageWidth(), $canvas->getImageHeight(), new ImagickPixel('transparent'));
        [$x, $y, $width, $height] = array_map(fn (float $value): float => $value * $scale, Boxes::on($paint, $scene, $shape));
        $layer->compositeImage($this->sample($paint, (int) max(1, round($width)), (int) max(1, round($height))), Imagick::COMPOSITE_OVER, (int) round($x), (int) round($y));

        if ($paths !== null) {
            $layer->compositeImage($this->mask($canvas, $paths, $scale), Imagick::COMPOSITE_DSTIN, 0, 0);
        }

        if ($opacity < 1) {
            $layer->evaluateImage(Imagick::EVALUATE_MULTIPLY, $opacity, Imagick::CHANNEL_ALPHA);
        }

        $canvas->compositeImage($layer, Imagick::COMPOSITE_OVER, 0, 0);
    }

    /**
     * The logo's plate, then the image, fitted into its box keeping its proportions and cut to its crop.
     */
    private function logo(Imagick $canvas, LogoLayer $layer, Scene $scene, float $scale): void
    {
        $style = $layer->style;

        if ($style->plate !== null) {
            $this->fill($canvas, $style->plate, [$layer->area], 1.0, $scene, $layer->area, $scale);
        }

        $image = new Imagick();
        $image->setBackgroundColor(new ImagickPixel('transparent'));
        // A density high enough that an SVG is drawn sharp at any size it is placed at.
        $image->setResolution(300, 300);
        $image->readImageBlob(self::readable($style->logo));

        [$x, $y, $width, $height] = array_map(fn (float $value): float => $value * $scale, $layer->box);
        $fit = min($width / $image->getImageWidth(), $height / $image->getImageHeight());
        [$imageWidth, $imageHeight] = [(int) max(1, round($image->getImageWidth() * $fit)), (int) max(1, round($image->getImageHeight() * $fit))];
        $image->resizeImage($imageWidth, $imageHeight, Imagick::FILTER_LANCZOS, 1);

        $placed = new Imagick();
        $placed->newImage($canvas->getImageWidth(), $canvas->getImageHeight(), new ImagickPixel('transparent'));
        $placed->compositeImage($image, Imagick::COMPOSITE_OVER, (int) round($x + ($width - $imageWidth) / 2), (int) round($y + ($height - $imageHeight) / 2));

        if ($layer->crop !== null) {
            $placed->compositeImage($this->mask($canvas, [$layer->crop], $scale), Imagick::COMPOSITE_DSTIN, 0, 0);
        }

        $canvas->compositeImage($placed, Imagick::COMPOSITE_OVER, 0, 0);
    }

    /**
     * The logo's bytes, when ImageMagick can read them: raster images always, SVG with its SVG support.
     */
    private static function readable(Logo $logo): string
    {
        return ! $logo->isSvg() || Imagick::queryFormats('SVG') !== [] ? $logo->contents : throw MissingExtensionException::svgLogos();
    }

    /**
     * Where the shapes are, as the alpha of an image the size of the canvas: measured by
     * `Coverage`, as GD's are, rather than drawn by ImageMagick, which smooths edges a little
     * too wide and, before version 7, leaves seams where shapes touch.
     *
     * @param list<Path> $paths
     */
    private function mask(Imagick $canvas, array $paths, float $scale): Imagick
    {
        [$width, $height] = [$canvas->getImageWidth(), $canvas->getImageHeight()];

        $mask = new Imagick();
        $mask->newImage($width, $height, new ImagickPixel('rgba(255,255,255,0)'));

        foreach (Coverage::of($paths, $scale, $width, $height) as $row => [$from, $alpha]) {
            $mask->importImagePixels($from, $row, count($alpha), 1, 'A', Imagick::PIXEL_FLOAT, $alpha);
        }

        return $mask;
    }

    /**
     * The gradient over a box of `$width` by `$height` pixels: sampled on a grid, scaled up smoothly.
     */
    private function sample(Gradient $gradient, int $width, int $height): Imagick
    {
        [$columns, $rows] = [max(2, min(self::GRADIENT_SAMPLES, $width)), max(2, min(self::GRADIENT_SAMPLES, $height))];
        $pixels = [];

        for ($row = 0; $row < $rows; $row++) {
            for ($column = 0; $column < $columns; $column++) {
                $rgba = $gradient->colorAt(($column + 0.5) / $columns, ($row + 0.5) / $rows)->toRgba();
                array_push($pixels, (int) round($rgba->red), (int) round($rgba->green), (int) round($rgba->blue), (int) round($rgba->alpha * 255));
            }
        }

        $image = new Imagick();
        $image->newImage($columns, $rows, new ImagickPixel('transparent'));
        $image->importImagePixels(0, 0, $columns, $rows, 'RGBA', Imagick::PIXEL_CHAR, $pixels);
        $image->resizeImage($width, $height, Imagick::FILTER_TRIANGLE, 1);

        return $image;
    }

    private function encode(Imagick $canvas): StreamBuffer
    {
        $format = $this->options->format;

        if (! $format->hasTransparency()) {
            $canvas->setImageBackgroundColor(new ImagickPixel('white'));
            $canvas = $canvas->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        }

        $canvas->setImageFormat($format->value);
        $canvas->setImageCompressionQuality($this->options->quality);
        $canvas->setOption('png:compression-level', (string) $this->options->compressionLevel);
        // The canvas is new, so there is nothing to strip. Without dates, the same code
        // gives the same bytes every time, which caching relies on.
        $canvas->setOption('png:exclude-chunks', 'date,time');

        $metadata = $this->options->metadata;

        if (! $metadata->isEmpty()) {
            $canvas->setImageProfile('xmp', $metadata->toXmp());

            foreach ($format === ImageFormat::Png ? $metadata->toTextChunks() : [] as $keyword => $value) {
                $canvas->setImageProperty($keyword, $value);
            }
        }

        if ($this->options->dpi !== null) {
            $canvas->setImageUnits(Imagick::RESOLUTION_PIXELSPERINCH);
            $canvas->setImageResolution($this->options->dpi, $this->options->dpi);
        }

        return $this->streams->buffer()->write($canvas->getImageBlob());
    }
}
