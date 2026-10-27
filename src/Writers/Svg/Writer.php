<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Svg;

use Closure;
use ExeQue\Qr\Colors\Cmyk;
use ExeQue\Qr\Colors\Named;
use ExeQue\Qr\Colors\Rgb;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidWriterOptionException;
use ExeQue\Qr\Geometry\CompactPathData;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Gradients\ColorStop;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Gradients\Enums\GradientBox;
use ExeQue\Qr\Gradients\Enums\Sampling;
use ExeQue\Qr\Gradients\LinearGradient;
use ExeQue\Qr\Gradients\RadialGradient;
use ExeQue\Qr\Logos\SvgIds;
use ExeQue\Qr\Paints\Layer;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Rendering\LogoLayer;
use ExeQue\Qr\Rendering\Scene;
use ExeQue\Qr\Rendering\SceneItem;
use ExeQue\Qr\Support\Number;
use ExeQue\Qr\Writers\Boxes;
use ExeQue\Qr\Writers\Contracts\Fakeable;
use ExeQue\Qr\Writers\Contracts\Writer as WriterContract;
use ExeQue\Qr\Writers\Metadata;
use ExeQue\Qr\Writers\StreamBuffer;
use ExeQue\Qr\Writers\StreamFactory;
use ExeQue\Qr\Writers\Svg\Enums\ColorNotation;
use WeakMap;

/**
 * Writes a scene as SVG: one `<path>` per paint, filled with the nonzero rule.
 *
 * Linear and radial gradients are written as SVG gradients; other kinds, and
 * gradients sampled per module, give each shape the color at its middle. A layered
 * paint is a path per layer, bottom first.
 */
final class Writer implements Fakeable, WriterContract
{
    /**
     * @var array<string, string> Gradient definitions by id, in the order first used.
     */
    private array $definitions = [];

    /**
     * @var WeakMap<LinearGradient|RadialGradient, string>|null A gradient's stops as elements, worked out once.
     */
    private ?WeakMap $stops = null;

    private float $scale = 1.0;

    public function __construct(
        private readonly Options $options = new Options(),
        private readonly StreamFactory $streams = new StreamFactory(),
    ) {
    }

    /**
     * A ready-made code reading "qr-fake".
     */
    public function fake(Scene $scene): Document
    {
        return new Document($this->streams->buffer()->write((string) file_get_contents(dirname(__DIR__, 3).'/resources/fakes/qr.svg')));
    }

    /**
     * Streams the SVG into a temporary stream: in memory while small, on disk beyond that.
     */
    public function write(Scene $scene): Document
    {
        $this->definitions = [];
        $this->scale = $this->options->pixels ? $this->options->size / $scene->size : 1.0;

        // The fills and the logo are worked out first: they decide what the <defs> need.
        $groups = $this->groups($scene);
        $background = $this->background($scene);
        $logo = $scene->logo === null ? '' : $this->logo($scene->logo, $scene);
        $extent = $this->number($scene->size);
        $viewBox = "0 0 {$extent} {$extent}";

        $stream = $this->streams->buffer();

        $stream->write($this->options->xmlDeclaration ? '<?xml version="1.0" encoding="UTF-8"?>' : '');
        $stream->write($this->options->symbol === null
            ? '<svg xmlns="http://www.w3.org/2000/svg" viewBox="'.$viewBox.'"'.$this->sizeAttributes().'>'
            : '<svg xmlns="http://www.w3.org/2000/svg" width="0" height="0" style="position:absolute"><symbol id="'.$this->options->symbol.'" viewBox="'.$viewBox.'">');
        $stream->write($this->metadata());
        // define(), called from the fills and the logo above, fills the definitions; PHPStan cannot follow that.
        // @phpstan-ignore identical.alwaysTrue
        $stream->write($this->definitions === [] ? '' : '<defs>'.implode('', $this->definitions).'</defs>');
        $stream->write($background);

        foreach ($groups as $group) {
            $this->writePath($stream, $group['fill'], $group['items']);
        }

        $stream->write($logo);

        $stream->write($this->options->symbol === null ? '</svg>' : '</symbol></svg>');

        return new Document($stream);
    }

    /**
     * Items with the same fill share one path, in the order their fills first appear.
     *
     * @return array<string, array{fill: string, items: list<SceneItem>}>
     */
    private function groups(Scene $scene): array
    {
        $groups = [];

        foreach ($scene->items as $index => $item) {
            if ($this->options->currentColor && $item->inheritsPaint) {
                $fill = $this->colorFill(new Named(Named::CURRENT_COLOR), $item->opacity);
                $groups[$fill] ??= ['fill' => $fill, 'items' => []];
                $groups[$fill]['items'][] = $item;

                continue;
            }

            foreach (self::layers($item->paint) as $layer => [$paint, $opacity]) {
                $fill = $this->paintFill($paint, $scene, $item->opacity * $opacity, fn (Gradient $gradient): array => Boxes::middle($gradient, $scene, $item));

                // A gradient in each shape's own box needs each shape as a path of its own; the layers
                // of a layered paint stay apart from plain fills, so each lands above the one under it.
                $key = ($item->paint instanceof Layered ? "layer {$layer} " : '').($this->isOwnBox($paint) ? "{$fill}#{$index}" : $fill);

                $groups[$key] ??= ['fill' => $fill, 'items' => []];
                $groups[$key]['items'][] = $item;
            }
        }

        return $groups;
    }

    /**
     * A paint as the paints drawn for it, bottom first, each with its own opacity.
     *
     * @return list<array{Paint, float}>
     */
    private static function layers(Paint $paint): array
    {
        return $paint instanceof Layered
            ? array_map(fn (Layer $layer): array => [$layer->paint, $layer->opacity], $paint->layers)
            : [[$paint, 1.0]];
    }

    /**
     * A fill for any paint: an SVG gradient where SVG has one, otherwise the
     * color a gradient has at `$middle`, its point in the gradient's box.
     *
     * @param Closure(Gradient): array{float, float} $middle
     */
    private function paintFill(Paint $paint, Scene $scene, float $opacity, Closure $middle): string
    {
        if ($this->isNative($paint)) {
            return 'fill="url(#'.$this->define($paint, $this->box($paint, $scene)).')"'.$this->opacity($opacity);
        }

        return $this->colorFill($this->color($paint instanceof Gradient ? $paint->colorAt(...$middle($paint)) : $paint), $opacity);
    }

    private function background(Scene $scene): string
    {
        if ($scene->background === null) {
            return '';
        }

        $extent = $this->number($scene->size);

        return implode('', array_map(
            fn (array $layer): string => "<rect width=\"{$extent}\" height=\"{$extent}\" {$this->paintFill($layer[0], $scene, $layer[1], fn (): array => [0.5, 0.5])}/>",
            self::layers($scene->background),
        ));
    }

    /**
     * The logo's plate, then the image: a raster image as an `<image>`, an SVG
     * inlined with its ids prefixed, or kept apart as an `<image>` when told to.
     */
    private function logo(LogoLayer $layer, Scene $scene): string
    {
        $style = $layer->style;
        $plate = '';

        if ($style->plate !== null) {
            foreach (self::layers($style->plate) as [$paint, $opacity]) {
                $plate .= "<path {$this->paintFill($paint, $scene, $opacity, fn (): array => [0.5, 0.5])} fill-rule=\"nonzero\" d=\"{$this->pathData($layer->area)}\"/>";
            }
        }

        [$x, $y, $width, $height] = array_map($this->number(...), $layer->box);
        $placement = "x=\"{$x}\" y=\"{$y}\" width=\"{$width}\" height=\"{$height}\" preserveAspectRatio=\"xMidYMid meet\"";

        $image = $style->logo->isSvg() && $this->options->inlineSvgLogos
            ? $this->inlineSvg($style->logo->contents, $placement, $style->logo->hash)
            : "<image {$placement} href=\"{$style->logo->toDataUri()}\"/>";

        if ($layer->crop !== null) {
            $id = ($this->options->symbol ?? 'qr').'-crop-'.substr(hash('xxh3', $layer->crop->toSvg()), 0, 8);
            $this->definitions[$id] ??= "<clipPath id=\"{$id}\"><path d=\"{$this->pathData($layer->crop)}\"/></clipPath>";
            $image = "<g clip-path=\"url(#{$id})\">{$image}</g>";
        }

        return $plate.$image;
    }

    /**
     * The sanitized SVG as a nested `<svg>`, placed, its ids prefixed so they cannot clash.
     */
    private function inlineSvg(string $svg, string $placement, string $hash): string
    {
        $prefixed = SvgIds::prefix($svg, ($this->options->symbol ?? 'qr').'-logo-'.substr($hash, 0, 8).'-');

        // Its own size, if any, gives way to the placement.
        $opening = (string) preg_replace('/\s(x|y|width|height|preserveAspectRatio)="[^"]*"/', '', substr($prefixed, 0, (int) strpos($prefixed, '>')));

        return $opening." {$placement}".substr($prefixed, (int) strpos($prefixed, '>'));
    }

    /**
     * The color in CSS a browser reads: device CMYK has no CSS notation yet, so it goes as sRGB.
     */
    private static function css(Color $color): string
    {
        return $color instanceof Cmyk ? Rgb::from($color)->toCss() : $color->toCss();
    }

    private function pathData(Path $path): string
    {
        return $this->options->compact
            ? new CompactPathData($this->options->precision, $this->scale)->encode($path)
            : $path->toSvg($this->options->precision, $this->scale);
    }

    /**
     * `<title>`, `<desc>` and the attribution in RDF; nothing without metadata.
     */
    private function metadata(): string
    {
        $metadata = $this->options->metadata;

        if ($metadata->isEmpty()) {
            return '';
        }

        return ($metadata->title === null ? '' : '<title>'.Metadata::escape($metadata->title).'</title>')
            .($metadata->description === null ? '' : '<desc>'.Metadata::escape($metadata->description).'</desc>')
            .'<metadata>'.$metadata->toRdf().'</metadata>';
    }

    /**
     * One `<path>` for a group, its shapes placed and written one by one.
     *
     * @param list<SceneItem> $items
     */
    private function writePath(StreamBuffer $stream, string $fill, array $items): void
    {
        $stream->write("<path {$fill} fill-rule=\"nonzero\" d=\"");
        $compact = new CompactPathData($this->options->precision, $this->scale);

        foreach ($items as $index => $item) {
            $path = $item->path();
            $stream->write($this->options->compact
                ? $compact->encode($path)
                : ($index === 0 ? '' : ' ').$path->toSvg($this->options->precision, $this->scale));
        }

        $stream->write('"/>');
    }

    private function colorFill(Color $color, float $opacity): string
    {
        if ($color instanceof Named && $color->isCurrentColor()) {
            return 'fill="currentColor"'.$this->opacity($color->alpha() * $opacity);
        }

        if ($this->options->colorNotation === ColorNotation::Css) {
            return 'fill="'.self::css($color).'"'.$this->opacity($opacity);
        }

        return 'fill="'.substr($color->toHex(), 0, 7).'"'.$this->opacity($color->alpha() * $opacity);
    }

    private function opacity(float $opacity): string
    {
        return $opacity < 1 ? ' fill-opacity="'.Number::format($opacity).'"' : '';
    }

    /**
     * Writes the gradient's definition once, under an id taken from its contents.
     *
     * @param array{float, float, float, float}|array{} $box `[x, y, width, height]`, or empty for each shape's own box.
     */
    private function define(LinearGradient|RadialGradient $gradient, array $box): string
    {
        $units = $box === [] ? '' : ' gradientUnits="userSpaceOnUse"';
        $spread = ' spreadMethod="'.$gradient->spread->value.'"';

        $attributes = $gradient instanceof LinearGradient
            ? $this->linearAttributes($gradient, $box)
            : $this->radialAttributes($gradient, $box);

        // The stops in sRGB take a while to work out, and a paint's gradient comes back for every shape.
        $this->stops ??= new WeakMap();
        $stops = $this->stops[$gradient] ??= implode('', array_map($this->stopElement(...), $gradient->srgbStops()));
        $element = $gradient instanceof LinearGradient ? 'linearGradient' : 'radialGradient';
        $body = "{$attributes}{$units}{$spread}>{$stops}</{$element}>";

        $id = ($this->options->symbol ?? 'qr').'-'.substr(hash('xxh3', $element.$body), 0, 8);
        $this->definitions[$id] ??= "<{$element} id=\"{$id}\"{$body}";

        return $id;
    }

    /**
     * @param array{float, float, float, float}|array{} $box
     */
    private function linearAttributes(LinearGradient $gradient, array $box): string
    {
        [[$x1, $y1], [$x2, $y2]] = $gradient->line();
        [$x1, $y1] = $this->inBox($x1, $y1, $box);
        [$x2, $y2] = $this->inBox($x2, $y2, $box);

        return ' x1="'.$this->number($x1).'" y1="'.$this->number($y1).'" x2="'.$this->number($x2).'" y2="'.$this->number($y2).'"';
    }

    /**
     * @param array{float, float, float, float}|array{} $box
     */
    private function radialAttributes(RadialGradient $gradient, array $box): string
    {
        [$cx, $cy] = $this->inBox($gradient->center[0], $gradient->center[1], $box);
        [$radiusX, $radiusY] = $gradient->radius;
        $width = $box === [] ? 1.0 : $box[2];

        $attributes = ' cx="'.$this->number($cx).'" cy="'.$this->number($cy).'" r="'.$this->number($radiusX * $width).'"';

        if ($gradient->focal !== null) {
            [$fx, $fy] = $this->inBox($gradient->focal[0], $gradient->focal[1], $box);
            $attributes .= ' fx="'.$this->number($fx).'" fy="'.$this->number($fy).'"';
        }

        // An ellipse is a circle squeezed about the center.
        if ($radiusX !== $radiusY) {
            $attributes .= sprintf(
                ' gradientTransform="translate(%1$s %2$s) scale(1 %3$s) translate(-%1$s -%2$s)"',
                $this->number($cx),
                $this->number($cy),
                Number::format($radiusY / $radiusX, $this->options->precision),
            );
        }

        return $attributes;
    }

    private function stopElement(ColorStop $stop): string
    {
        $color = $this->color($stop->color);
        $colorValue = $this->options->colorNotation === ColorNotation::Css ? self::css($color) : substr($color->toHex(), 0, 7);
        $opacity = $this->options->colorNotation === ColorNotation::Hex && $color->alpha() < 1
            ? ' stop-opacity="'.Number::format($color->alpha()).'"'
            : '';

        return '<stop offset="'.Number::format($stop->position, 4).'" stop-color="'.$colorValue.'"'.$opacity.'/>';
    }

    /**
     * A point of the gradient's box on the canvas; the box itself when it is each shape's own.
     *
     * @param array{float, float, float, float}|array{} $box
     *
     * @return array{float, float}
     */
    private function inBox(float $x, float $y, array $box): array
    {
        return $box === [] ? [$x, $y] : [$box[0] + $x * $box[2], $box[1] + $y * $box[3]];
    }

    /**
     * @return array{float, float, float, float}|array{}
     */
    private function box(Gradient $gradient, Scene $scene): array
    {
        // Each shape's own box is the SVG default, objectBoundingBox: no coordinates needed.
        return Boxes::of($gradient) === GradientBox::Shape ? [] : Boxes::on($gradient, $scene);
    }

    /**
     * @phpstan-assert-if-true LinearGradient|RadialGradient $paint
     */
    private function isNative(Paint $paint): bool
    {
        return ($paint instanceof LinearGradient || $paint instanceof RadialGradient) && $paint->sampling === Sampling::Continuous;
    }

    private function isOwnBox(Paint $paint): bool
    {
        return $this->isNative($paint) && Boxes::of($paint) === GradientBox::Shape;
    }

    private function color(Paint $paint): Color
    {
        if (! $paint instanceof Color) {
            throw InvalidWriterOptionException::unsupportedPaint($paint);
        }

        return $paint;
    }

    private function sizeAttributes(): string
    {
        return $this->options->size === null ? '' : " width=\"{$this->options->size}\" height=\"{$this->options->size}\"";
    }

    private function number(float $value): string
    {
        return Number::format($value * $this->scale, $this->options->precision);
    }
}
