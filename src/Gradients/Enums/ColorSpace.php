<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients\Enums;

/**
 * The space a gradient blends its colors in.
 */
enum ColorSpace: string
{
    case Oklab = 'oklab';
    case Oklch = 'oklch';
    case Srgb = 'srgb';
    case LinearSrgb = 'srgb-linear';
    case Lch = 'lch';
    case Hsl = 'hsl';

    /**
     * Whether the space has a hue, which blends around the color wheel.
     */
    public function isPolar(): bool
    {
        return in_array($this, [self::Oklch, self::Lch, self::Hsl], true);
    }
}
