<?php

declare(strict_types=1);

namespace ExeQue\Qr\Support;

/**
 * Randomness that is the same every time for the same seed and purpose.
 *
 * @internal
 */
final class Seed
{
    /**
     * A number from 0 up to 1 for `$seed`, different for each `$purpose`.
     */
    public static function unit(int $seed, string $purpose = ''): float
    {
        // A hash that mixes every bit: with a linear one (CRC) purposes of the same length move together.
        return (hexdec(substr(hash('xxh3', "{$seed}:{$purpose}"), 0, 7)) & 0x7FFFFFF) / 0x7FFFFFF;
    }

    /**
     * A number from `$min` to `$max`.
     */
    public static function between(int $seed, float $min, float $max, string $purpose = ''): float
    {
        return $min + ($max - $min) * self::unit($seed, $purpose);
    }

    /**
     * One of the options, more likely the higher its weight.
     *
     * @template T
     *
     * @param non-empty-list<T> $options
     * @param list<float>|null $weights One per option; equal when null.
     *
     * @return T
     */
    public static function pick(int $seed, array $options, ?array $weights = null, string $purpose = ''): mixed
    {
        $weights ??= array_fill(0, count($options), 1.0);
        $target = self::unit($seed, $purpose) * array_sum($weights);

        foreach ($options as $index => $option) {
            $target -= $weights[$index];

            if ($target < 0) {
                return $option;
            }
        }

        return $options[array_key_last($options)];
    }
}
