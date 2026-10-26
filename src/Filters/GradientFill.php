<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Gradients\Contracts\Gradient;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Support\Hydrate;
use ExeQue\Qr\Support\Normalizer;

/**
 * Paints each module the color a gradient has at its middle, across the symbol.
 */
final class GradientFill implements ModuleFilter, NamedFilter
{
    use Scoped;

    private Gradient $gradient;

    /**
     * @param Gradient|array<string, mixed> $gradient A gradient, or its stored array.
     */
    public function __construct(Gradient|array $gradient)
    {
        $this->gradient = Hydrate::gradient($gradient);
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        return $style->withPaint($this->gradient->colorAt($cell->x / $cell->size, $cell->y / $cell->size));
    }

    public function name(): string
    {
        return 'gradient-fill';
    }

    public function options(): array
    {
        return ['gradient' => Normalizer::gradient($this->gradient), ...$this->scope()];
    }
}
