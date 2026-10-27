<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\Symmetric;

/**
 * A pumpkin of three lobes under a short stem. It stands upright on every corner: a mirrored style leaves
 * it as it is.
 */
final readonly class Pumpkin implements InnerEyeShape, Symmetric
{
    public function path(): Path
    {
        return Figures::pumpkin(0, -0.2, 3.05, 3.3);
    }
}
