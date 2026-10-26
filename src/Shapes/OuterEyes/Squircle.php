<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\OuterEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;

/**
 * A squircle ring: a square with soft, full sides.
 */
final readonly class Squircle implements OuterEyeShape
{
    public function path(): Path
    {
        return Figures::squircle(0, 0, 7, 7)->append(Figures::squircle(0, 0, 5, 5)->reversed());
    }
}
