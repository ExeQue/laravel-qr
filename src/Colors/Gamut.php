<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

/**
 * Brings colors into sRGB the way CSS Color Module Level 4 section 13.2
 * describes: lower the chroma in OkLCh, keeping lightness and hue, until the
 * color fits or clipping it is no longer visible.
 *
 * @internal
 */
final class Gamut
{
    // The just-noticeable difference in Oklab, and the search precision.
    private const float JND = 0.02;

    private const float EPSILON = 0.0001;

    /**
     * The color in sRGB, every channel within 0-1.
     *
     * @param array{float, float, float} $xyz
     *
     * @return array{float, float, float}
     */
    public static function toSrgb(array $xyz): array
    {
        $rgb = Convert::xyzToSrgb($xyz);

        if (self::inGamut($rgb)) {
            return self::clip($rgb);
        }

        [$lightness, $chroma, $hue] = Convert::toPolar(Convert::xyzToOklab($xyz));

        if ($lightness >= 1) {
            return [1.0, 1.0, 1.0];
        }

        if ($lightness <= 0) {
            return [0.0, 0.0, 0.0];
        }

        return self::reduceChroma($lightness, $chroma, $hue);
    }

    /**
     * Binary search for the highest chroma whose clipped color is within a JND of itself.
     *
     * @return array{float, float, float}
     */
    private static function reduceChroma(float $lightness, float $chroma, float $hue): array
    {
        $clipped = self::clip(self::oklchToSrgb($lightness, $chroma, $hue));

        if (self::difference($clipped, $lightness, $chroma, $hue) < self::JND) {
            return $clipped;
        }

        $low = 0.0;
        $high = $chroma;
        $lowInGamut = true;

        while ($high - $low > self::EPSILON) {
            $candidate = ($low + $high) / 2;
            $rgb = self::oklchToSrgb($lightness, $candidate, $hue);

            if ($lowInGamut && self::inGamut($rgb)) {
                $low = $candidate;

                continue;
            }

            $clipped = self::clip($rgb);
            $difference = self::difference($clipped, $lightness, $candidate, $hue);

            if ($difference >= self::JND) {
                $high = $candidate;

                continue;
            }

            // Clipping is all but exactly one JND away: as close as it gets.
            if (self::JND - $difference < self::EPSILON) {
                break;
            }

            $lowInGamut = false;
            $low = $candidate;
        }

        return $clipped;
    }

    /**
     * @return array{float, float, float}
     */
    private static function oklchToSrgb(float $lightness, float $chroma, float $hue): array
    {
        return Convert::xyzToSrgb(Convert::oklabToXyz(Convert::fromPolar([$lightness, $chroma, $hue])));
    }

    /**
     * The distance in Oklab between a clipped color and the OkLCh color it came from.
     *
     * @param array{float, float, float} $clipped
     */
    private static function difference(array $clipped, float $lightness, float $chroma, float $hue): float
    {
        $a = Convert::xyzToOklab(Convert::srgbToXyz($clipped));
        $b = Convert::fromPolar([$lightness, $chroma, $hue]);

        return sqrt(($a[0] - $b[0]) ** 2 + ($a[1] - $b[1]) ** 2 + ($a[2] - $b[2]) ** 2);
    }

    /**
     * @param array{float, float, float} $rgb
     */
    private static function inGamut(array $rgb): bool
    {
        foreach ($rgb as $channel) {
            if ($channel < -self::EPSILON || $channel > 1 + self::EPSILON) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{float, float, float} $rgb
     *
     * @return array{float, float, float}
     */
    private static function clip(array $rgb): array
    {
        return array_map(fn (float $channel): float => max(0.0, min(1.0, $channel)), $rgb);
    }
}
