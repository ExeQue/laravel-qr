<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\OuterEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;

/**
 * A regular hexagonal ring, flat along the top and bottom and 7 modules tall. Its points reach
 * half a module past the sides of the box, into the light separator around the eye, so the
 * ring still measures seven modules across the middle both ways.
 */
final readonly class Hexagon implements OuterEyeShape
{
    public function path(): Path
    {
        return Figures::hexagon(0, 0, 7 * 2 / M_SQRT3, 7)->append(Figures::hexagon(0, 0, 5 * 2 / M_SQRT3, 5)->reversed());
    }
}
