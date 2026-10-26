<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A full four-pointed sparkle, reaching a little past the 3x3 box at its points.
 */
final readonly class Sparkle implements InnerEyeShape
{
    public function path(): Path
    {
        // Full sides: a thin sparkle is too light in the middle for some scanners.
        return Figures::sparkle(0, 0, 1.7, 0.75);
    }
}
