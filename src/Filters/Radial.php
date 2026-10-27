<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters;

use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Filters\Concerns\AppliesProperty;
use ExeQue\Qr\Filters\Concerns\Scoped;
use ExeQue\Qr\Filters\Contracts\ModuleFilter;
use ExeQue\Qr\Filters\Contracts\NamedFilter;
use ExeQue\Qr\Filters\Enums\Property;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Rendering\ModuleStyle;
use ExeQue\Qr\Validation\Contracts\HasOptionRules;
use ExeQue\Qr\Validation\Rules\Options;
use Illuminate\Validation\Rule;

/**
 * Varies a property from a center outward: `$inner` there, `$outer` at the farthest corner.
 */
final class Radial implements ModuleFilter, NamedFilter, HasOptionRules
{
    use AppliesProperty;
    use Scoped;

    private Property $property;

    /**
     * @param array<array-key, mixed> $center A point of the symbol, 0-1 on both axes: `[x, y]`.
     */
    public function __construct(
        Property|string $property = Property::Size,
        private float $inner = 1.0,
        private float $outer = 0.8,
        private array $center = [0.5, 0.5],
    ) {
        $this->property = $property instanceof Property ? $property : Property::from($property);
        // As floats whatever they came as (form strings, whole numbers from JSON), so equal filters are equal.
        if (! array_is_list($center) || count($center) !== 2 || ! array_all($center, fn (mixed $number): bool => is_numeric($number))) {
            throw InvalidOptionException::notAList(self::class, 'center', 2);
        }

        $this->center = array_map(floatval(...), $center);
    }

    public function module(ModuleContext $cell, ModuleStyle $style): ModuleStyle
    {
        if (! $this->appliesTo($cell)) {
            return $style;
        }

        [$centerX, $centerY] = [$this->center[0] * $cell->size, $this->center[1] * $cell->size];

        // The farthest corner is the furthest any cell can be.
        $reach = sqrt(max($centerX, $cell->size - $centerX) ** 2 + max($centerY, $cell->size - $centerY) ** 2);
        $distance = sqrt(($cell->x - $centerX) ** 2 + ($cell->y - $centerY) ** 2) / $reach;

        return $this->apply($this->property, $style, $this->inner + ($this->outer - $this->inner) * $distance);
    }

    public static function optionRules(): array
    {
        return [
            'property' => ['sometimes', 'filled', Rule::enum(Property::class)],
            'inner' => Options::number(0, 2),
            'outer' => Options::number(0, 2),
            'center' => ['sometimes', 'list', 'size:2'],
            'center.*' => ['numeric', 'between:0,1'],
        ];
    }

    public function name(): string
    {
        return 'radial';
    }

    public function options(): array
    {
        return ['property' => $this->property->value, 'inner' => $this->inner, 'outer' => $this->outer, 'center' => $this->center, ...$this->scope()];
    }
}
