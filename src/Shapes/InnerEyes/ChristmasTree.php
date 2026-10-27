<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\Symmetric;

/**
 * A fir tree of three tiers on a short trunk. It stands upright on every corner: a mirrored style
 * leaves it as it is.
 */
final readonly class ChristmasTree implements InnerEyeShape, Symmetric
{
    public function path(): Path
    {
        return Figures::christmasTree(0, 0, 3.8, 3.6);
    }
}
