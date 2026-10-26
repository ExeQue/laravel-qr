<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Exceptions\InvalidFilterException;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Support\Seed;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Options;

/**
 * Turns each module by its own angle: anywhere in a range, or one of a few steps.
 */
final class MixedRotation implements ModuleFilter, NamedFilter, HasOptionRules
{
    use Scoped;

    /**
     * @var non-empty-list<float>|null
     */
    private ?array $steps;

    /**
     * @param list<float>|null $steps Snapped turns, such as `[0, 90, 180, 270]`, instead of the range.
     * @param int $seed Another seed gives other angles.
     */
    public function __construct(
        private float $min = -20,
        private float $max = 20,
        ?array $steps = null,
        private int $seed = 0,
    ) {
        if ($steps === []) {
            throw InvalidFilterException::weightsDoNotMatch($this->name(), 0, null);
        }

        // Numbers from a form are strings; a list of them is not converted on the way in.
        $this->steps = $steps === null ? null : array_map(floatval(...), $steps);
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        $purpose = "{$this->name()}:{$this->seed}";
        $degrees = $this->steps === null
            ? $cell->random($this->min, $this->max, $purpose)
            : Seed::pick($cell->seed, $this->steps, purpose: $purpose);

        return $style->rotateBy($degrees);
    }

    public static function optionRules(): array
    {
        return [
            'min' => Options::number(-180, 180),
            'max' => Options::number(-180, 180),
            'steps' => ['sometimes', 'nullable', 'list', 'min:1', 'max:16'],
            'steps.*' => ['numeric', 'between:-360,360'],
            'seed' => Options::integer(),
        ];
    }

    public function name(): string
    {
        return 'mixed-rotation';
    }

    public function options(): array
    {
        return [...array_filter(['min' => $this->min, 'max' => $this->max, 'steps' => $this->steps, 'seed' => $this->seed], fn (mixed $value): bool => $value !== null), ...$this->scope()];
    }
}
