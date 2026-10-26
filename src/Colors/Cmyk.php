<?php

declare(strict_types=1);

namespace ExeQue\Qr\Colors;

use ExeQue\Qr\Colors\Concerns\IsColor;
use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Support\Number;

/**
 * Cyan, magenta, yellow and black, 0-100 % each, written `cmyk(0% 91% 68% 25%)`.
 * Converted the plain arithmetic way, without an ICC profile.
 */
final readonly class Cmyk implements Color
{
    use IsColor;

    public function __construct(
        public float $cyan,
        public float $magenta,
        public float $yellow,
        public float $black,
        public float $alpha = 1.0,
    ) {
        self::checkRange('cyan', $cyan, 0, 100);
        self::checkRange('magenta', $magenta, 0, 100);
        self::checkRange('yellow', $yellow, 0, 100);
        self::checkRange('black', $black, 0, 100);
        self::checkRange('alpha', $alpha, 0, 1);
    }

    /**
     * The same as `new`, for chaining.
     */
    public static function make(float $cyan, float $magenta, float $yellow, float $black, float $alpha = 1.0): self
    {
        return new self($cyan, $magenta, $yellow, $black, $alpha);
    }

    public static function fromXyz(array $xyz, float $alpha): static
    {
        $rgb = Gamut::toSrgb($xyz);
        $black = 1 - max($rgb);

        if ($black >= 1) {
            return new self(0, 0, 0, 100, $alpha);
        }

        [$cyan, $magenta, $yellow] = array_map(fn (float $channel): float => (1 - $channel - $black) / (1 - $black) * 100, $rgb);

        return new self($cyan, $magenta, $yellow, $black * 100, $alpha);
    }

    public function toXyz(): array
    {
        $black = $this->black / 100;

        return Convert::srgbToXyz(array_map(
            fn (float $ink): float => (1 - $ink / 100) * (1 - $black),
            [$this->cyan, $this->magenta, $this->yellow],
        ));
    }

    public function toCss(): string
    {
        $inks = implode(' ', array_map(fn (float $ink): string => Number::format($ink).'%', [$this->cyan, $this->magenta, $this->yellow, $this->black]));

        return $this->alpha < 1 ? "cmyk({$inks} / ".Number::format($this->alpha).')' : "cmyk({$inks})";
    }
}
