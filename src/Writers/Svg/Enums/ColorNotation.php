<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Svg\Enums;

/**
 * How SVG output writes colors.
 */
enum ColorNotation: string
{
    /** `#rrggbb` with `fill-opacity`: what every reader of SVG understands. */
    case Hex = 'hex';

    /** Each color in its own CSS notation, such as `oklch(55% 0.24 293)`. */
    case Css = 'css';
}
