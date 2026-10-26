<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Concerns;

use ExeQue\Qr\Exceptions\InvalidOptionException;

/**
 * A configurable shape whose options are its promoted constructor properties.
 *
 * @internal
 */
trait HasOptions
{
    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        return get_object_vars($this);
    }

    private static function within(string $option, float $value, float $min, float $max): float
    {
        return $value >= $min && $value <= $max ? $value : throw InvalidOptionException::outOfRange(static::class, $option, $min, $max, $value);
    }

    /**
     * A number, or one per corner, each within the range; as floats.
     *
     * @param float|array<array-key, mixed> $value
     *
     * @return float|array{float, float, float, float}
     */
    private static function corners(string $option, float|array $value, float $min, float $max): float|array
    {
        if (! is_array($value)) {
            return self::within($option, $value, $min, $max);
        }

        if (! array_is_list($value) || count($value) !== 4 || ! array_all($value, fn (mixed $number): bool => is_numeric($number))) {
            throw InvalidOptionException::notAList(static::class, $option, 4);
        }

        [$first, $second, $third, $fourth] = $value;

        return [
            self::within($option, (float) $first, $min, $max),
            self::within($option, (float) $second, $min, $max),
            self::within($option, (float) $third, $min, $max),
            self::within($option, (float) $fourth, $min, $max),
        ];
    }
}
