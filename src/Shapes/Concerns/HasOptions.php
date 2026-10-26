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
}
