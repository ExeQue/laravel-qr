<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;

/**
 * Rotation, size and offset mixed at once, for a hand-placed look.
 */
final class Jitter implements ModuleFilter, NamedFilter
{
    use Scoped;

    /**
     * @param float $rotate Up to this many degrees either way.
     * @param float $size Up to this share smaller.
     * @param float $offset Up to this many modules each way.
     */
    public function __construct(
        private float $rotate = 10,
        private float $size = 0.1,
        private float $offset = 0.05,
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
            ->rotateBy($cell->random(-$this->rotate, $this->rotate, "{$purpose}:rotate"))
            ->scaleBy(1 - $cell->random(0, $this->size, "{$purpose}:size"))
            ->offsetBy(
                $cell->random(-$this->offset, $this->offset, "{$purpose}:x"),
                $cell->random(-$this->offset, $this->offset, "{$purpose}:y"),
            );
    }

    public function name(): string
    {
        return 'jitter';
    }

    public function options(): array
    {
        return ['rotate' => $this->rotate, 'size' => $this->size, 'offset' => $this->offset, 'seed' => $this->seed, ...$this->scope()];
    }
}
