<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A squircle block: a square with soft, full sides.
 */
final readonly class Squircle implements InnerEyeShape
{
    public function path(): Path
    {
        return Figures::squircle(0, 0, 3, 3);
    }
}
