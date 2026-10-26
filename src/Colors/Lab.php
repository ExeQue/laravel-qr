<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Colors\Concerns\IsColor;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Support\Number;

/**
 * CIE Lab against D50, as CSS has it: lightness 0-100, a and b unbounded,
 * written `lab(54.29% 80.8 69.89)`. May lie outside sRGB.
 */
final readonly class Lab implements Color
{
    use IsColor;

    public function __construct(
        public float $lightness,
        public float $a,
        public float $b,
        public float $alpha = 1.0,
    ) {
        self::checkRange('lightness', $lightness, 0, 100);
        self::checkRange('alpha', $alpha, 0, 1);
    }

    /**
     * The same as `new`, for chaining.
     */
    public static function make(float $lightness, float $a, float $b, float $alpha = 1.0): self
    {
        return new self($lightness, $a, $b, $alpha);
    }

    public static function fromXyz(array $xyz, float $alpha): static
    {
        [$lightness, $a, $b] = Convert::xyzToLab($xyz);

        return new self(max(0.0, min(100.0, $lightness)), $a, $b, $alpha);
    }

    public function toXyz(): array
    {
        return Convert::labToXyz([$this->lightness, $this->a, $this->b]);
    }

    public function toCss(): string
    {
        $components = Number::format($this->lightness).'% '.Number::format($this->a).' '.Number::format($this->b);

        return $this->alpha < 1 ? "lab({$components} / ".Number::format($this->alpha).')' : "lab({$components})";
    }
}
