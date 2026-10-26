<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients\Enums;

/**
 * Which way round the color wheel a polar space blends two hues (CSS Color Module Level 4, section 12.4).
 */
enum HueInterpolation: string
{
    case Shorter = 'shorter';
    case Longer = 'longer';
    case Increasing = 'increasing';
    case Decreasing = 'decreasing';
}
