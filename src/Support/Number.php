<?php

declare(strict_types=1);

namespace ExeQue\Qr\Support;

/**
 * @internal
 */
final class Number
{
    /**
     * `$value` with at most `$decimals` decimals and no trailing zeros, always
     * with a decimal point, whatever the locale.
     */
    public static function format(float $value, int $decimals = 3): string
    {
        $formatted = number_format($value, $decimals, '.', '');

        if (str_contains($formatted, '.')) {
            $formatted = rtrim(rtrim($formatted, '0'), '.');
        }

        return $formatted === '-0' ? '0' : $formatted;
    }
}
