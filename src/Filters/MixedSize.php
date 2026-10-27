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
 * Scales each module by its own factor in a range.
 */
final class MixedSize implements ModuleFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @param int $seed Another seed gives other values.
     */
    public function __construct(
        private float $min = 0.85,
        private float $max = 1.0,
        private int $seed = 0,
    ) {
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        $purpose = "{$this->name()}:{$this->seed}";

        return $style->scaleBy($cell->random($this->min, $this->max, $purpose));
    }

    public static function optionRules(): array
    {
        return [
            'min' => Options::number(0, 2),
            'max' => Options::number(0, 2),
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'mixed-size';
    }

    public function options(): array
    {
        return ['min' => $this->min, 'max' => $this->max, 'seed' => $this->seed, ...$this->scope()];
    }
}
