<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Colors\Concerns\IsColor;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Support\Number;

/**
 * OkLCh: lightness 0-1, chroma from 0, hue 0-360, written `oklch(62.8% 0.258 29.23)`.
 * May lie outside sRGB.
 */
final readonly class Oklch implements Color
{
    use IsColor;

    public function __construct(
        public float $lightness,
        public float $chroma,
        public float $hue,
        public float $alpha = 1.0,
    ) {
        self::checkRange('lightness', $lightness, 0, 1);
        self::checkRange('chroma', $chroma, 0, INF);
        self::checkRange('hue', $hue, 0, 360);
        self::checkRange('alpha', $alpha, 0, 1);
    }

    /**
     * The same as `new`, for chaining.
     */
    public static function make(float $lightness, float $chroma, float $hue, float $alpha = 1.0): self
    {
        return new self($lightness, $chroma, $hue, $alpha);
    }

    public static function fromXyz(array $xyz, float $alpha): static
    {
        [$lightness, $chroma, $hue] = Convert::toPolar(Convert::xyzToOklab($xyz));

        return new self(max(0.0, min(1.0, $lightness)), $chroma, $hue, $alpha);
    }

    public function toXyz(): array
    {
        return Convert::oklabToXyz(Convert::fromPolar([$this->lightness, $this->chroma, $this->hue]));
    }

    public function toCss(): string
    {
        $components = Number::format($this->lightness * 100).'% '.Number::format($this->chroma).' '.Number::format($this->hue);

        return $this->alpha < 1 ? "oklch({$components} / ".Number::format($this->alpha).')' : "oklch({$components})";
    }
}
