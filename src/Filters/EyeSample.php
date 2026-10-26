<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\EyeFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Paints\Layer;
use ExeQue\Qr\Paints\Layered;
use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;

/**
 * Gives each eye the color the modules' gradient has where the eye sits, so a gradient
 * across the code runs through the eyes too, each a single color.
 */
final class EyeSample implements EyeFilter, NamedFilter
{
    use Scoped;

    public function __construct(
        private bool $outer = true,
        private bool $inner = true,
    ) {
    }

    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle
    {
        if (! $this->appliesToEye($eye) || $eye->modules === null) {
            return $style;
        }

        $paint = self::sample($eye->modules, $eye->x / $eye->size, $eye->y / $eye->size);

        return $style
            ->withOuter($this->outer ? $style->outer->withPaint($paint) : $style->outer)
            ->withInner($this->inner ? $style->inner->withPaint($paint) : $style->inner);
    }

    public function name(): string
    {
        return 'eye-sample';
    }

    public function options(): array
    {
        return ['outer' => $this->outer, 'inner' => $this->inner, ...$this->scope()];
    }

    /**
     * The paint at a point of the code, 0-1: a gradient's color there, each layer's of a layered paint.
     */
    private static function sample(Paint $paint, float $x, float $y): Paint
    {
        return match (true) {
            $paint instanceof Gradient => $paint->colorAt($x, $y),
            $paint instanceof Layered => new Layered(array_map(fn (Layer $layer): Layer => new Layer(self::sample($layer->paint, $x, $y), $layer->opacity), $paint->layers)),
            default => $paint,
        };
    }
}
