<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters\Concerns;

use ExeQue\Qr\Exceptions\InvalidOptionException;

/**
 * For filters with an option that is a number, or a range of two to pick from per eye.
 *
 * @internal
 */
trait TakesRanges
{
    /**
     * The number, or the range as two floats, whatever they came as (form strings, whole numbers from JSON).
     *
     * @param float|array<array-key, mixed> $value
     *
     * @return float|array{float, float}
     */
    private static function range(string $option, float|array $value): float|array
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value) || count($value) !== 2 || ! array_all($value, fn (mixed $number): bool => is_numeric($number))) {
            throw InvalidOptionException::notAList(self::class, $option, 2);
        }

        return [(float) $value[0], (float) $value[1]];
    }
}
