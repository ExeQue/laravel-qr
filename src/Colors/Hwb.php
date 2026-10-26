<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Colors\Concerns\IsColor;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Support\Number;

/**
 * Hue 0-360, whiteness and blackness 0-100 %, written `hwb(345 7% 25%)`.
 */
final readonly class Hwb implements Color
{
    use IsColor;

    public function __construct(
        public float $hue,
        public float $whiteness,
        public float $blackness,
        public float $alpha = 1.0,
    ) {
        self::checkRange('hue', $hue, 0, 360);
        self::checkRange('whiteness', $whiteness, 0, 100);
        self::checkRange('blackness', $blackness, 0, 100);
        self::checkRange('alpha', $alpha, 0, 1);
    }

    /**
     * The same as `new`, for chaining.
     */
    public static function make(float $hue, float $whiteness, float $blackness, float $alpha = 1.0): self
    {
        return new self($hue, $whiteness, $blackness, $alpha);
    }

    public static function fromXyz(array $xyz, float $alpha): static
    {
        [$hue, $whiteness, $blackness] = Convert::srgbToHwb(Gamut::toSrgb($xyz));

        return new self($hue, $whiteness * 100, $blackness * 100, $alpha);
    }

    public function toXyz(): array
    {
        return Convert::srgbToXyz(Convert::hwbToSrgb($this->hue, $this->whiteness / 100, $this->blackness / 100));
    }

    public function toCss(): string
    {
        $components = Number::format($this->hue).' '.Number::format($this->whiteness).'% '.Number::format($this->blackness).'%';

        return $this->alpha < 1 ? "hwb({$components} / ".Number::format($this->alpha).')' : "hwb({$components})";
    }
}
