<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos\Enums;

/**
 * The area cleared for a logo, which is also how the logo and its plate are cropped.
 */
enum LogoShape: string
{
    case Square = 'square';
    case Rounded = 'rounded';
    case Circle = 'circle';

    /** Only the modules under the logo's visible pixels, for irregular logos. */
    case Alpha = 'alpha';
}
