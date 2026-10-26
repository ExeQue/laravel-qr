<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Exceptions\InvalidColorException;

/**
 * One component of a CSS color function.
 *
 * @internal
 */
final class Component
{
    private const array DEGREES_PER = [
        'deg' => 1,
        'grad' => 0.9,
        'rad' => 180 / M_PI,
        'turn' => 360,
    ];

    /**
     * A number, a percentage of `$hundredPercent`, or `none` (zero).
     */
    public static function number(string $token, float $hundredPercent): float
    {
        if ($token === 'none') {
            return 0.0;
        }

        if (str_ends_with($token, '%')) {
            return self::numeric(substr($token, 0, -1), $token) * $hundredPercent / 100;
        }

        return self::numeric($token, $token);
    }

    /**
     * A hue in degrees, from 0 up to 360: a number, an angle (`deg`, `grad`, `rad`, `turn`) or `none`.
     */
    public static function hue(string $token): float
    {
        if ($token === 'none') {
            return 0.0;
        }

        preg_match('/^(.*?)(deg|grad|rad|turn)?$/', $token, $match);

        return Convert::normalizeHue(self::numeric($match[1] ?? $token, $token) * self::DEGREES_PER[$match[2] ?? 'deg']);
    }

    private static function numeric(string $number, string $token): float
    {
        if (! is_numeric($number)) {
            throw InvalidColorException::unparsable($token);
        }

        return (float) $number;
    }
}
