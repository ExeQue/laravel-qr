<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients;

use ExeQue\Qr\Colors\Convert;
use ExeQue\Qr\Colors\Hsl;
use ExeQue\Qr\Colors\Lch;
use ExeQue\Qr\Colors\Oklab;
use ExeQue\Qr\Colors\Oklch;
use ExeQue\Qr\Colors\Rgb;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Gradients\Enums\ColorSpace;
use ExeQue\Qr\Gradients\Enums\HueInterpolation;

/**
 * Blends two colors the way CSS Color Module Level 4 section 12 does:
 * in a chosen space, with premultiplied alpha, polar hues taking the chosen way round.
 *
 * @internal
 */
final class Interpolator
{
    // Below this chroma (or saturation) a color has no hue to speak of, and takes the other color's.
    private const float POWERLESS = 1e-4;

    public static function mix(Color $from, Color $to, float $amount, ColorSpace $space, HueInterpolation $hue): Color
    {
        $start = self::coordinates($from, $space);
        $end = self::coordinates($to, $space);
        $hueIndex = self::hueIndex($space);

        if ($hueIndex !== null) {
            [$start[$hueIndex], $end[$hueIndex]] = self::hues($start, $end, $hueIndex, $hue);
        }

        $alpha = $from->alpha() + ($to->alpha() - $from->alpha()) * $amount;
        $mixed = [];

        foreach ($start as $index => $value) {
            if ($index === $hueIndex) {
                $mixed[] = Convert::normalizeHue($value + ($end[$index] - $value) * $amount);

                continue;
            }

            $premultiplied = $value * $from->alpha() + ($end[$index] * $to->alpha() - $value * $from->alpha()) * $amount;
            $mixed[] = $alpha > 0 ? $premultiplied / $alpha : $premultiplied;
        }

        return self::fromCoordinates($mixed, $alpha, $space);
    }

    /**
     * @return array{float, float, float}
     */
    private static function coordinates(Color $color, ColorSpace $space): array
    {
        return match ($space) {
            ColorSpace::Oklab => [($c = Oklab::from($color))->lightness, $c->a, $c->b],
            ColorSpace::Oklch => [($c = Oklch::from($color))->lightness, $c->chroma, $c->hue],
            ColorSpace::Lch => [($c = Lch::from($color))->lightness, $c->chroma, $c->hue],
            ColorSpace::Hsl => [($c = Hsl::from($color))->hue, $c->saturation, $c->lightness],
            ColorSpace::Srgb => self::srgb($color),
            ColorSpace::LinearSrgb => array_map(Convert::toLinear(...), self::srgb($color)),
        };
    }

    /**
     * @param list<float> $coordinates
     */
    private static function fromCoordinates(array $coordinates, float $alpha, ColorSpace $space): Color
    {
        [$first, $second, $third] = $coordinates;

        return match ($space) {
            ColorSpace::Oklab => new Oklab(self::clamp($first, 1), $second, $third, $alpha),
            ColorSpace::Oklch => new Oklch(self::clamp($first, 1), max(0.0, $second), $third, $alpha),
            ColorSpace::Lch => new Lch(self::clamp($first, 100), max(0.0, $second), $third, $alpha),
            ColorSpace::Hsl => new Hsl($first, self::clamp($second, 100), self::clamp($third, 100), $alpha),
            ColorSpace::Srgb => new Rgb(...[...array_map(fn (float $channel): float => self::clamp($channel, 1) * 255, $coordinates), $alpha]),
            ColorSpace::LinearSrgb => new Rgb(...[...array_map(fn (float $channel): float => self::clamp(Convert::toGamma($channel), 1) * 255, $coordinates), $alpha]),
        };
    }

    /**
     * The two hues, one of them moved by a turn so that blending them goes the chosen way round.
     *
     * @param array{float, float, float} $start
     * @param array{float, float, float} $end
     *
     * @return array{float, float}
     */
    private static function hues(array $start, array $end, int $hueIndex, HueInterpolation $interpolation): array
    {
        // Hue sits first in HSL, last in LCh; the strength of the color is the component between.
        [$from, $to] = [$start[$hueIndex], $end[$hueIndex]];

        if ($start[1] < self::POWERLESS) {
            $from = $to;
        }

        if ($end[1] < self::POWERLESS) {
            $to = $from;
        }

        $difference = $to - $from;

        return match ($interpolation) {
            HueInterpolation::Shorter => match (true) {
                $difference > 180 => [$from + 360, $to],
                $difference < -180 => [$from, $to + 360],
                default => [$from, $to],
            },
            HueInterpolation::Longer => match (true) {
                $difference > 0 && $difference < 180 => [$from + 360, $to],
                $difference > -180 && $difference <= 0 => [$from, $to + 360],
                default => [$from, $to],
            },
            HueInterpolation::Increasing => $to < $from ? [$from, $to + 360] : [$from, $to],
            HueInterpolation::Decreasing => $from < $to ? [$from + 360, $to] : [$from, $to],
        };
    }

    private static function hueIndex(ColorSpace $space): ?int
    {
        return match ($space) {
            ColorSpace::Oklch, ColorSpace::Lch => 2,
            ColorSpace::Hsl => 0,
            default => null,
        };
    }

    /**
     * @return array{float, float, float}
     */
    private static function srgb(Color $color): array
    {
        $rgb = $color->toRgba();

        return [$rgb->red / 255, $rgb->green / 255, $rgb->blue / 255];
    }

    private static function clamp(float $value, float $max): float
    {
        return max(0.0, min($max, $value));
    }
}
