<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidWriterOptionException;
use ExeQue\Qr\Exceptions\MissingExtensionException;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\Sampling;
use ExeQue\Qr\Logos\Logo;
use ExeQue\Qr\Paints\Layer;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Rendering\LogoLayer;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\SceneItem;
use ExeQue\Qr\Writers\Boxes;
use ExeQue\Qr\Writers\Contracts\Fakeable;
use ExeQue\Qr\Writers\Contracts\Writer as WriterContract;
use ExeQue\Qr\Writers\Raster\Enums\ImageFormat;
use ExeQue\Qr\Writers\StreamBuffer;
use ExeQue\Qr\Writers\StreamFactory;
use Imagick;
use ImagickDraw;
use ImagickPixel;

/**
 * Draws a scene as a PNG, JPEG or WebP with Imagick: real curves, anti-aliased,
 * drawn in pixels so they come out smooth, filled with the nonzero rule.
 *
 * Gradients are sampled on a coarse grid, scaled up smoothly and laid over the
 * shapes; gradients sampled per module give each shape the color at its middle.
 */
final readonly class Writer implements Fakeable, WriterContract
{
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
            $whole = Path::polygon([[0, 0], [$scene->size, 0], [$scene->size, $scene->size], [0, $scene->size]]);

            $this->fill($canvas, $scene->background, [$whole], 1.0, $scene, null, $scale);
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
        $file = dirname(__DIR__, 3).'/resources/fakes/qr.'.$this->options->format->extension();

        return new Image($this->streams->buffer()->write((string) file_get_contents($file)), $this->options->format);
    }

    /**
     * Items with the same paint and opacity share one path. A gradient in each
     * shape's own box needs each shape on its own; one sampled per module, a color per shape.
     *
     * @return list<array{paint: Paint, opacity: float, paths: list<Path>, item: SceneItem}>
     */
    private function groups(Scene $scene): array
    {
        $groups = [];

        foreach ($scene->items as $index => $item) {
            $layers = $item->paint instanceof Layered ? $item->paint->layers : [new Layer($item->paint)];

            foreach ($layers as $layer => $part) {
                $paint = $part->paint;

                if ($paint instanceof Gradient && $this->isSampledPerModule($paint)) {
                    $paint = $paint->colorAt(...Boxes::middle($paint, $scene, $item));
                }

                $opacity = $part->opacity * $item->opacity;

                // The layers of a layered paint stay apart from plain fills, so each lands above the one under it.
                $key = ($item->paint instanceof Layered ? "layer {$layer} " : '').match (true) {
                    $paint instanceof ColorContract => $paint->toCss(),
                    $paint instanceof Gradient && Boxes::of($paint) === GradientBox::Shape => spl_object_id($paint)."#{$index}",
                    default => (string) spl_object_id($paint),
                }.'@'.$opacity;

                $groups[$key] ??= ['paint' => $paint, 'opacity' => $opacity, 'paths' => [], 'item' => $item];
                $groups[$key]['paths'][] = $item->path();
            }
        }

        return array_values($groups);
    }

    /**
     * @param list<Path> $paths
     * @param SceneItem|Path|null $shape The first of the items painted, or the logo's plate; null for the background.
     */
    private function fill(Imagick $canvas, Paint $paint, array $paths, float $opacity, Scene $scene, SceneItem|Path|null $shape, float $scale): void
    {
        if ($paint instanceof ColorContract) {
            $canvas->drawImage($this->draw($paths, $scale, $paint, $opacity));

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
        $mask = new Imagick();
        $mask->newImage($canvas->getImageWidth(), $canvas->getImageHeight(), new ImagickPixel('transparent'));
        $mask->drawImage($this->draw($paths, $scale, Color::parse('#ffffff'), 1.0));

        $layer = new Imagick();
        $layer->newImage($canvas->getImageWidth(), $canvas->getImageHeight(), new ImagickPixel('transparent'));
        [$x, $y, $width, $height] = array_map(fn (float $value): float => $value * $scale, Boxes::on($paint, $scene, $shape));
        $layer->compositeImage($this->sample($paint, (int) max(1, round($width)), (int) max(1, round($height))), Imagick::COMPOSITE_OVER, (int) round($x), (int) round($y));
        $layer->compositeImage($mask, Imagick::COMPOSITE_DSTIN, 0, 0);

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
            $crop = new Imagick();
            $crop->newImage($canvas->getImageWidth(), $canvas->getImageHeight(), new ImagickPixel('transparent'));
            $crop->drawImage($this->draw([$layer->crop], $scale, Color::parse('#ffffff'), 1.0));
            $placed->compositeImage($crop, Imagick::COMPOSITE_DSTIN, 0, 0);
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
     * @param list<Path> $paths
     */
    private function draw(array $paths, float $scale, ColorContract $color, float $opacity): ImagickDraw
    {
        $rgba = $color->toRgba();

        $draw = new ImagickDraw();
        $draw->setFillColor(new ImagickPixel(sprintf('rgb(%d,%d,%d)', round($rgba->red), round($rgba->green), round($rgba->blue))));
        $draw->setFillOpacity($rgba->alpha * $opacity);
        $draw->setFillRule(Imagick::FILLRULE_NONZERO);
        $draw->setStrokeOpacity(0);
        $draw->pathStart();

        foreach ($paths as $path) {
            foreach ($path->commands() as $command) {
                $points = array_map(fn (float|string $value): float => (float) $value * $scale, array_slice($command, 1));

                match ($command[0]) {
                    'M' => $draw->pathMoveToAbsolute($points[0], $points[1]),
                    'L' => $draw->pathLineToAbsolute($points[0], $points[1]),
                    'C' => $draw->pathCurveToAbsolute($points[0], $points[1], $points[2], $points[3], $points[4], $points[5]),
                    default => $draw->pathClose(),
                };
            }
        }

        $draw->pathFinish();

        return $draw;
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

    private function isSampledPerModule(Gradient $gradient): bool
    {
        return isset($gradient->sampling) && $gradient->sampling === Sampling::PerModule;
    }
}
