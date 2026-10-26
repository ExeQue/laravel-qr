<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Colors\Concerns\IsColor;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Support\Number;

/**
 * sRGB with alpha, written the legacy way: `rgba(190, 18, 60, 0.8)`.
 */
final readonly class Rgba implements Color
{
    use IsColor;

    public function __construct(
        public float $red,
        public float $green,
        public float $blue,
        public float $alpha = 1.0,
    ) {
        self::checkRange('red', $red, 0, 255);
        self::checkRange('green', $green, 0, 255);
        self::checkRange('blue', $blue, 0, 255);
        self::checkRange('alpha', $alpha, 0, 1);
    }

    /**
     * The same as `new`, for chaining.
     */
    public static function make(float $red, float $green, float $blue, float $alpha = 1.0): self
    {
        return new self($red, $green, $blue, $alpha);
    }

    public static function fromXyz(array $xyz, float $alpha): static
    {
        [$red, $green, $blue] = Gamut::toSrgb($xyz);

        return new self($red * 255, $green * 255, $blue * 255, $alpha);
    }

    public function toXyz(): array
    {
        return Convert::srgbToXyz([$this->red / 255, $this->green / 255, $this->blue / 255]);
    }

    public function toRgba(): Rgba
    {
        return $this;
    }

    public function toCss(): string
    {
        $components = array_map(fn (float $value): string => Number::format($value), [$this->red, $this->green, $this->blue, $this->alpha]);

        return 'rgba('.implode(', ', $components).')';
    }
}
