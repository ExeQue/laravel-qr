<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A six-petalled flower.
 */
final readonly class Flower implements InnerEyeShape
{
    public function path(): Path
    {
        return Figures::flower(0, 0, 0.6, 0.9, 1.05);
    }
}
