<?php

declare(strict_types=1);

namespace ExeQue\Qr\Contracts;

use ExeQue\Qr\Colors\Rgba;

interface Color extends Paint
{
    /**
     * The color in sRGB, 0-255 per channel, brought into gamut where it lies outside.
     */
    public function toRgba(): Rgba;

    /**
     * Opacity, 0 to 1.
     */
    public function alpha(): float;

    public function withAlpha(float $alpha): static;

    /**
     * The color in its own CSS notation, such as `oklch(62.8% 0.258 29.23)`.
     */
    public function toCss(): string;

    /**
     * `#rrggbb`, or `#rrggbbaa` when the color is not opaque.
     */
    public function toHex(): string;

    /**
     * Lighter by `$percent` points of perceived lightness (OKLCH), 0 to 100; white at most.
     */
    public function lighten(float $percent): self;

    /**
     * Darker by `$percent` points of perceived lightness (OKLCH), 0 to 100; black at most.
     */
    public function darken(float $percent): self;

    /**
     * The hue turned by `$degrees` round the OKLCH color wheel, lightness and chroma kept.
     */
    public function shift(float $degrees): self;

    /**
     * The opposite color in sRGB: each channel's distance from full.
     */
    public function invert(): self;
}
