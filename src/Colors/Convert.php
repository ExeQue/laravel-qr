<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

/**
 * Conversions between color spaces, through CIE XYZ (D65). Components are
 * floats: sRGB 0-1, Lab and LCh as CSS has them, Oklab L 0-1.
 *
 * The matrices and formulas are from CSS Color Module Level 4, section 18
 * ("Sample code for color conversions").
 *
 * @internal
 */
final class Convert
{
    private const array LINEAR_SRGB_TO_XYZ = [
        [0.41239079926595934, 0.357584339383878, 0.1804807884018343],
        [0.21263900587151027, 0.715168678767756, 0.07219231536073371],
        [0.01933081871559182, 0.11919477979462598, 0.9505321522496607],
    ];

    private const array XYZ_TO_LINEAR_SRGB = [
        [3.2409699419045226, -1.537383177570094, -0.4986107602930034],
        [-0.9692436362808796, 1.8759675015077202, 0.04155505740717559],
        [0.05563007969699366, -0.20397695888897652, 1.0569715142428786],
    ];

    // Bradford chromatic adaptation, for Lab and LCh, which CSS defines against D50.
    private const array D65_TO_D50 = [
        [1.0479298208405488, 0.022946793341019088, -0.05019222954313557],
        [0.029627815688159344, 0.990434484573249, -0.01707382502938514],
        [-0.009243058152591178, 0.015055144896577895, 0.7518742899580008],
    ];

    private const array D50_TO_D65 = [
        [0.9554734527042182, -0.023098536874261423, 0.0632593086610217],
        [-0.028369706963208136, 1.0099954580058226, 0.021041398966943008],
        [0.012314001688319899, -0.020507696433477912, 1.3303659366080753],
    ];

    private const array D50_WHITE = [0.3457 / 0.3585, 1.0, (1.0 - 0.3457 - 0.3585) / 0.3585];

    private const array XYZ_TO_LMS = [
        [0.8190224379967030, 0.3619062600528904, -0.1288737815209879],
        [0.0329836539323885, 0.9292868615863434, 0.0361446663506424],
        [0.0481771893596242, 0.2642395317527308, 0.6335478284694309],
    ];

    private const array LMS_TO_XYZ = [
        [1.2268798758459243, -0.5578149944602171, 0.2813910456659647],
        [-0.0405757452148008, 1.1122868032803170, -0.0717110580655164],
        [-0.0763729366746601, -0.4214933324022432, 1.5869240198367816],
    ];

    private const array LMS_TO_OKLAB = [
        [0.2104542683093140, 0.7936177747023054, -0.0040720430116193],
        [1.9779985324311684, -2.4285922420485799, 0.4505937096174110],
        [0.0259040424655478, 0.7827717124575296, -0.8086757549230774],
    ];

    private const array OKLAB_TO_LMS = [
        [1.0, 0.3963377773761749, 0.2158037573099136],
        [1.0, -0.1055613458156586, -0.0638541728258133],
        [1.0, -0.0894841775298119, -1.2914855480194092],
    ];

    // Below this spread between channels a color is gray and has no hue.
    private const float ACHROMATIC = 1e-7;

    // CIE constants for Lab: ε = 216/24389, κ = 24389/27.
    private const float EPSILON = 216 / 24389;

    private const float KAPPA = 24389 / 27;

    /**
     * @param array{float, float, float} $rgb
     *
     * @return array{float, float, float}
     */
    public static function srgbToXyz(array $rgb): array
    {
        return self::multiply(self::LINEAR_SRGB_TO_XYZ, array_map(self::toLinear(...), $rgb));
    }

    /**
     * Unclamped: a color outside sRGB comes back with channels outside 0-1.
     *
     * @param array{float, float, float} $xyz
     *
     * @return array{float, float, float}
     */
    public static function xyzToSrgb(array $xyz): array
    {
        return array_map(self::toGamma(...), self::multiply(self::XYZ_TO_LINEAR_SRGB, $xyz));
    }

    /**
     * @param array{float, float, float} $xyz
     *
     * @return array{float, float, float}
     */
    public static function xyzToLab(array $xyz): array
    {
        $adapted = self::multiply(self::D65_TO_D50, $xyz);

        [$fx, $fy, $fz] = array_map(
            fn (float $value, float $white): float => self::labCompand($value / $white),
            $adapted,
            self::D50_WHITE,
        );

        return [116 * $fy - 16, 500 * ($fx - $fy), 200 * ($fy - $fz)];
    }

    /**
     * @param array{float, float, float} $lab
     *
     * @return array{float, float, float}
     */
    public static function labToXyz(array $lab): array
    {
        [$lightness, $a, $b] = $lab;

        $fy = ($lightness + 16) / 116;
        $fx = $a / 500 + $fy;
        $fz = $fy - $b / 200;

        $adapted = [
            $fx ** 3 > self::EPSILON ? $fx ** 3 : (116 * $fx - 16) / self::KAPPA,
            $lightness > self::KAPPA * self::EPSILON ? $fy ** 3 : $lightness / self::KAPPA,
            $fz ** 3 > self::EPSILON ? $fz ** 3 : (116 * $fz - 16) / self::KAPPA,
        ];

        $white = self::D50_WHITE;
        $scaled = [$adapted[0] * $white[0], $adapted[1] * $white[1], $adapted[2] * $white[2]];

        return self::multiply(self::D50_TO_D65, $scaled);
    }

    /**
     * @param array{float, float, float} $xyz
     *
     * @return array{float, float, float}
     */
    public static function xyzToOklab(array $xyz): array
    {
        $lms = self::multiply(self::XYZ_TO_LMS, $xyz);

        return self::multiply(self::LMS_TO_OKLAB, [self::cubeRoot($lms[0]), self::cubeRoot($lms[1]), self::cubeRoot($lms[2])]);
    }

    /**
     * @param array{float, float, float} $oklab
     *
     * @return array{float, float, float}
     */
    public static function oklabToXyz(array $oklab): array
    {
        $lms = array_map(fn (float $value): float => $value ** 3, self::multiply(self::OKLAB_TO_LMS, $oklab));

        return self::multiply(self::LMS_TO_XYZ, $lms);
    }

    /**
     * Lab to LCh, or Oklab to OkLCh: chroma and hue (degrees, 0-360) from a and b.
     *
     * @param array{float, float, float} $lab
     *
     * @return array{float, float, float}
     */
    public static function toPolar(array $lab): array
    {
        [$lightness, $a, $b] = $lab;

        return [$lightness, sqrt($a ** 2 + $b ** 2), self::normalizeHue(rad2deg(atan2($b, $a)))];
    }

    /**
     * @param array{float, float, float} $lch
     *
     * @return array{float, float, float}
     */
    public static function fromPolar(array $lch): array
    {
        [$lightness, $chroma, $hue] = $lch;

        return [$lightness, $chroma * cos(deg2rad($hue)), $chroma * sin(deg2rad($hue))];
    }

    /**
     * @param float $saturation 0-1
     * @param float $lightness 0-1
     *
     * @return array{float, float, float}
     */
    public static function hslToSrgb(float $hue, float $saturation, float $lightness): array
    {
        $amount = $saturation * min($lightness, 1 - $lightness);
        $channel = function (int $offset) use ($hue, $lightness, $amount): float {
            $k = fmod($offset + self::normalizeHue($hue) / 30, 12);

            return $lightness - $amount * max(min($k - 3, 9 - $k, 1), -1);
        };

        return [$channel(0), $channel(8), $channel(4)];
    }

    /**
     * @param array{float, float, float} $rgb
     *
     * @return array{float, float, float} Hue in degrees, saturation and lightness 0-1.
     */
    public static function srgbToHsl(array $rgb): array
    {
        $max = max($rgb);
        $min = min($rgb);
        $lightness = ($max + $min) / 2;
        $delta = $max - $min;

        if ($delta < self::ACHROMATIC) {
            return [0.0, 0.0, $lightness];
        }

        $saturation = $lightness === 0.0 || $lightness === 1.0 ? 0.0 : ($max - $lightness) / min($lightness, 1 - $lightness);

        return [self::hue($rgb, $max, $delta), $saturation, $lightness];
    }

    /**
     * @param float $whiteness 0-1
     * @param float $blackness 0-1
     *
     * @return array{float, float, float}
     */
    public static function hwbToSrgb(float $hue, float $whiteness, float $blackness): array
    {
        if ($whiteness + $blackness >= 1) {
            $gray = $whiteness / ($whiteness + $blackness);

            return [$gray, $gray, $gray];
        }

        return array_map(
            fn (float $channel): float => $channel * (1 - $whiteness - $blackness) + $whiteness,
            self::hslToSrgb($hue, 1, 0.5),
        );
    }

    /**
     * @param array{float, float, float} $rgb
     *
     * @return array{float, float, float} Hue in degrees, whiteness and blackness 0-1.
     */
    public static function srgbToHwb(array $rgb): array
    {
        $max = max($rgb);
        $min = min($rgb);
        $delta = $max - $min;

        return [$delta < self::ACHROMATIC ? 0.0 : self::hue($rgb, $max, $delta), $min, 1 - $max];
    }

    public static function normalizeHue(float $hue): float
    {
        $hue = fmod($hue, 360);

        return $hue < 0 ? $hue + 360 : $hue;
    }

    /**
     * @param array{float, float, float} $rgb
     */
    private static function hue(array $rgb, float $max, float $delta): float
    {
        [$red, $green, $blue] = $rgb;

        $hue = match ($max) {
            $red => ($green - $blue) / $delta + ($green < $blue ? 6 : 0),
            $green => ($blue - $red) / $delta + 2,
            default => ($red - $green) / $delta + 4,
        };

        return $hue * 60;
    }

    public static function toLinear(float $channel): float
    {
        $magnitude = abs($channel);

        return ($channel <=> 0) * ($magnitude <= 0.04045 ? $magnitude / 12.92 : (($magnitude + 0.055) / 1.055) ** 2.4);
    }

    public static function toGamma(float $channel): float
    {
        $magnitude = abs($channel);

        return ($channel <=> 0) * ($magnitude <= 0.0031308 ? 12.92 * $magnitude : 1.055 * $magnitude ** (1 / 2.4) - 0.055);
    }

    private static function cubeRoot(float $value): float
    {
        return ($value <=> 0) * abs($value) ** (1 / 3);
    }

    private static function labCompand(float $value): float
    {
        return $value > self::EPSILON ? $value ** (1 / 3) : (self::KAPPA * $value + 16) / 116;
    }

    /**
     * @param array<int, array{float, float, float}> $matrix
     * @param array{float, float, float} $vector
     *
     * @return array{float, float, float}
     */
    private static function multiply(array $matrix, array $vector): array
    {
        [$x, $y, $z] = $vector;

        return [
            $matrix[0][0] * $x + $matrix[0][1] * $y + $matrix[0][2] * $z,
            $matrix[1][0] * $x + $matrix[1][1] * $y + $matrix[1][2] * $z,
            $matrix[2][0] * $x + $matrix[2][1] * $y + $matrix[2][2] * $z,
        ];
    }
}
