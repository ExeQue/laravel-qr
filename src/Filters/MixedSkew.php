<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;

/**
 * Skews each module its own way, up to `$x` and `$y` degrees.
 */
final class MixedSkew implements ModuleFilter, NamedFilter
{
    use Scoped;

    public function __construct(
        private float $x = 10,
        private float $y = 0,
        private int $seed = 0,
    ) {
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        $purpose = "{$this->name()}:{$this->seed}";

        return $style->withSkew(
            $style->skewX + $cell->random(-$this->x, $this->x, "{$purpose}:x"),
            $style->skewY + $cell->random(-$this->y, $this->y, "{$purpose}:y"),
        );
    }

    public function name(): string
    {
        return 'mixed-skew';
    }

    public function options(): array
    {
        return ['x' => $this->x, 'y' => $this->y, 'seed' => $this->seed, ...$this->scope()];
    }
}
