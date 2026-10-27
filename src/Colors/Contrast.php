<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Contracts\Color as ColorContract;

/**
 * WCAG 2 contrast between two colors, from their sRGB values.
 */
final class Contrast
{
    /**
     * From 1 (none) to 21 (black on white). A translucent color counts as it
     * shows over the other; a translucent background as it shows over white.
     */
    public static function ratio(ColorContract $color, ColorContract $background, float $opacity = 1.0): float
    {
        $ground = self::over(self::channels($background), $background->alpha(), [1.0, 1.0, 1.0]);
        $shown = self::over(self::channels($color), $color->alpha() * $opacity, $ground);

        [$lighter, $darker] = [max(self::luminance($shown), self::luminance($ground)), min(self::luminance($shown), self::luminance($ground))];

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Whether the color shows lighter than the background: a light code on a dark ground, which
     * not every reader scans. Translucent colors count as `ratio()` counts them.
     */
    public static function lighter(ColorContract $color, ColorContract $background, float $opacity = 1.0): bool
    {
        $ground = self::over(self::channels($background), $background->alpha(), [1.0, 1.0, 1.0]);
        $shown = self::over(self::channels($color), $color->alpha() * $opacity, $ground);

        return self::luminance($shown) > self::luminance($ground);
    }

    /**
     * @return array{float, float, float}
     */
    private static function channels(ColorContract $color): array
    {
        $rgba = $color->toRgba();

        return [$rgba->red / 255, $rgba->green / 255, $rgba->blue / 255];
    }

    /**
     * @param array{float, float, float} $color
     * @param array{float, float, float} $ground
     *
     * @return array{float, float, float}
     */
    private static function over(array $color, float $alpha, array $ground): array
    {
        return [
            $color[0] * $alpha + $ground[0] * (1 - $alpha),
            $color[1] * $alpha + $ground[1] * (1 - $alpha),
            $color[2] * $alpha + $ground[2] * (1 - $alpha),
        ];
    }

    /**
     * @param array{float, float, float} $rgb
     */
    private static function luminance(array $rgb): float
    {
        [$red, $green, $blue] = array_map(Convert::toLinear(...), $rgb);

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }
}
