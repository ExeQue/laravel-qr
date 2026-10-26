<?php

declare(strict_types=1);

namespace ExeQue\Qr\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A number, or a list of a set length of numbers, each within a range: an option such
 * as a tilt (`4`, or `[-5, 5]` to pick from) or corner radii (`0.7`, or one per corner).
 *
 * @internal
 */
final class Numbers implements ValidationRule
{
    /**
     * Run on empty strings too, which Laravel otherwise passes.
     */
    public bool $implicit = true;

    public function __construct(
        private readonly float $min,
        private readonly float $max,
        private readonly int $count,
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $numbers = is_array($value) ? $value : [$value];
        $fits = (! is_array($value) || (array_is_list($value) && count($value) === $this->count))
            && array_all($numbers, fn (mixed $number): bool => is_numeric($number) && $number >= $this->min && $number <= $this->max);

        if (! $fits) {
            $fail(trans('qr::validation.numbers', ['min' => $this->min, 'max' => $this->max, 'count' => $this->count]));
        }
    }
}
