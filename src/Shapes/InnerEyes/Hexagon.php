<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A regular hexagon, flat along the top and bottom like the hexagon ring, 3 modules tall; its
 * points reach a little past the 3x3 box.
 */
final readonly class Hexagon implements InnerEyeShape
{
    public function path(): Path
    {
        return Figures::hexagon(0, 0, 3 * 2 / M_SQRT3, 3);
    }
}
