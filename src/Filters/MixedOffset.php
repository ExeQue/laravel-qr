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
 * Nudges each module its own way, up to `$max` modules on each axis.
 */
final class MixedOffset implements ModuleFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    public function __construct(
        private float $max = 0.1,
        private int $seed = 0,
    ) {
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        $purpose = "{$this->name()}:{$this->seed}";

        return $style->offsetBy(
            $cell->random(-$this->max, $this->max, "{$purpose}:x"),
            $cell->random(-$this->max, $this->max, "{$purpose}:y"),
        );
    }

    public static function optionRules(): array
    {
        return [
            'max' => Options::number(0, 1),
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'mixed-offset';
    }

    public function options(): array
    {
        return ['max' => $this->max, 'seed' => $this->seed, ...$this->scope()];
    }
}
