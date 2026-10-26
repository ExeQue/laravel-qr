<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A five-pointed star, its weight on the middle.
 */
final readonly class Star implements InnerEyeShape
{
    public function path(): Path
    {
        return Figures::star(0, 0.12, 1.8, 1.2, 5);
    }
}
