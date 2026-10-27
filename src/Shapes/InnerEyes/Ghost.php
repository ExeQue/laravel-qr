<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\Symmetric;

/**
 * A ghost, its head round and its hem in scallops. It floats upright on every corner: a mirrored style
 * leaves it as it is.
 */
final readonly class Ghost implements InnerEyeShape, Symmetric
{
    public function path(): Path
    {
        return Figures::ghost(0, -0.08, 3.35, 3.15);
    }
}
