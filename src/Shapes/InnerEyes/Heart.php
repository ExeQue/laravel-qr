<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\Directional;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A heart leaning 45 degrees, its weight moved onto the middle of the eye.
 */
final readonly class Heart implements Directional, InnerEyeShape
{
    public function path(): Path
    {
        return Figures::heart(0, 0, 1.2, lean: -45, shift: 0.3);
    }

    public function isDirectional(): bool
    {
        return true;
    }
}
