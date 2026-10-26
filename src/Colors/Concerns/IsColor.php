<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors\Concerns;

use Closure;
use ExeQue\Qr\Colors\Convert;
use ExeQue\Qr\Colors\Gamut;
use ExeQue\Qr\Colors\Hex;
use ExeQue\Qr\Colors\Oklch;
use ExeQue\Qr\Colors\Rgba;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Exceptions\InvalidColorException;
use ReflectionClass;

/**
 * What every color class shares. A class supplies `toXyz()` and
 * `fromXyz()`; this converts between them and every other space.
 *
 * @internal
 *
 * @phpstan-require-implements Color
 */
trait IsColor
{
    /**
     * The color in CIE XYZ (D65), outside sRGB if it is.
     *
     * @internal
     *
     * @return array{float, float, float}
     */
    abstract public function toXyz(): array;

    /**
     * @internal
     *
     * @param array{float, float, float} $xyz
     */
    abstract public static function fromXyz(array $xyz, float $alpha): static;

    /**
     * Any color, in this class's space.
     */
    public static function from(Color $color): static
    {
        return static::fromXyz(self::xyzOf($color), $color->alpha());
    }

    /**
     * Rebuilds a color from `var_export()`, as `php artisan config:cache` writes config:
     * a color instance in a config file survives caching.
     *
     * @param array<string, mixed> $properties
     */
    public static function __set_state(array $properties): static
    {
        $color = new ReflectionClass(static::class)->newInstanceWithoutConstructor();

        foreach ($properties as $name => $value) {
            (fn () => $this->{$name} = $value)->call($color);
        }

        return $color;
    }

    public function alpha(): float
    {
        return $this->alpha;
    }

    public function withAlpha(float $alpha): static
    {
        self::checkRange('alpha', $alpha, 0, 1);

        return clone($this, ['alpha' => $alpha]);
    }

    public function toRgba(): Rgba
    {
        [$red, $green, $blue] = Gamut::toSrgb($this->toXyz());

        return new Rgba($red * 255, $green * 255, $blue * 255, $this->alpha);
    }

    public function toHex(): string
    {
        return Hex::from($this)->toCss();
    }

    /**
     * The color is rebuilt in its own class where that can hold it; a named color that
     * becomes one without a name comes back as hex.
     */
    public function lighten(float $percent): Color
    {
        return $this->inOklch(fn (Oklch $color): Oklch => new Oklch(max(0.0, min(1.0, $color->lightness + $percent / 100)), $color->chroma, $color->hue, $color->alpha));
    }

    public function darken(float $percent): Color
    {
        return $this->inOklch(fn (Oklch $color): Oklch => new Oklch(max(0.0, min(1.0, $color->lightness - $percent / 100)), $color->chroma, $color->hue, $color->alpha));
    }

    public function shift(float $degrees): Color
    {
        return $this->inOklch(fn (Oklch $color): Oklch => new Oklch($color->lightness, $color->chroma, fmod(fmod($color->hue + $degrees, 360) + 360, 360), $color->alpha));
    }

    public function invert(): Color
    {
        $rgba = $this->toRgba();

        return $this->rebuilt(new Rgba(255 - $rgba->red, 255 - $rgba->green, 255 - $rgba->blue, $rgba->alpha));
    }

    /**
     * @param Closure(Oklch): Oklch $change
     */
    private function inOklch(Closure $change): Color
    {
        return $this->rebuilt($change(Oklch::from($this)));
    }

    private function rebuilt(Color $color): Color
    {
        try {
            return static::from($color);
        } catch (InvalidColorException) {
            return Hex::from($color);
        }
    }

    /**
     * @return array{float, float, float}
     */
    private static function xyzOf(Color $color): array
    {
        if (method_exists($color, 'toXyz')) {
            return $color->toXyz();
        }

        $rgba = $color->toRgba();

        return Convert::srgbToXyz([$rgba->red / 255, $rgba->green / 255, $rgba->blue / 255]);
    }

    private static function checkRange(string $component, float $value, float $min, float $max): void
    {
        if ($value < $min || $value > $max) {
            throw InvalidColorException::outOfRange(static::class, $component, $value, $min, $max);
        }
    }
}
