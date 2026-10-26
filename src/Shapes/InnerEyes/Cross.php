<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A plus with broad bars, reaching a little past the 3x3 box.
 */
final readonly class Cross implements InnerEyeShape
{
    public function path(): Path
    {
        // Bars narrower than this leave too little in the middle for some scanners.
        return Figures::cross(0, 0, 1.6, 0.85);
    }
}
