<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A four-leaf clover.
 */
final readonly class Clover implements InnerEyeShape
{
    public function path(): Path
    {
        return Figures::clover(0, 0, 0.6, 0.8, 0.8);
    }
}
