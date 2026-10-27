<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Each module tilted in depth by an amount of its own, about both axes, and turned a little: tiles
 * laid by hand.
 */
final class Jitter3d implements ModuleFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @param float $tilt Up to this many degrees either way, about each axis.
     * @param float $rotate Up to this many degrees either way, on the screen.
     */
    public function __construct(
        private float $tilt = 25,
        private float $rotate = 0,
        private int $seed = 0,
    ) {
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        $purpose = "{$this->name()}:{$this->seed}";

        return $style
            ->tiltBy(
                $cell->random(-$this->tilt, $this->tilt, "{$purpose}:x"),
                $cell->random(-$this->tilt, $this->tilt, "{$purpose}:y"),
            )
            ->rotateBy($cell->random(-$this->rotate, $this->rotate, "{$purpose}:rotate"));
    }

    public static function optionRules(): array
    {
        return [
            'tilt' => Options::number(0, 85),
            'rotate' => Options::number(0, 45),
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'jitter-3d';
    }

    public function options(): array
    {
        return ['tilt' => $this->tilt, 'rotate' => $this->rotate, 'seed' => $this->seed, ...$this->scope()];
    }
}
