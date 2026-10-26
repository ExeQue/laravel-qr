<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Colors\Concerns\IsColor;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Support\Number;

/**
 * As `Hsl`, written the legacy way: `hsla(345, 83%, 41%, 0.8)`.
 */
final readonly class Hsla implements Color
{
    use IsColor;

    public function __construct(
        public float $hue,
        public float $saturation,
        public float $lightness,
        public float $alpha = 1.0,
    ) {
        self::checkRange('hue', $hue, 0, 360);
        self::checkRange('saturation', $saturation, 0, 100);
        self::checkRange('lightness', $lightness, 0, 100);
        self::checkRange('alpha', $alpha, 0, 1);
    }

    /**
     * The same as `new`, for chaining.
     */
    public static function make(float $hue, float $saturation, float $lightness, float $alpha = 1.0): self
    {
        return new self($hue, $saturation, $lightness, $alpha);
    }

    public static function fromXyz(array $xyz, float $alpha): static
    {
        [$hue, $saturation, $lightness] = Convert::srgbToHsl(Gamut::toSrgb($xyz));

        return new self($hue, $saturation * 100, $lightness * 100, $alpha);
    }

    public function toXyz(): array
    {
        return Convert::srgbToXyz(Convert::hslToSrgb($this->hue, $this->saturation / 100, $this->lightness / 100));
    }

    public function toCss(): string
    {
        return sprintf(
            'hsla(%s, %s%%, %s%%, %s)',
            Number::format($this->hue),
            Number::format($this->saturation),
            Number::format($this->lightness),
            Number::format($this->alpha),
        );
    }
}
