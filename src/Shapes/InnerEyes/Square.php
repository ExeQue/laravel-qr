<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * The standard 3x3 square.
 */
final readonly class Square implements InnerEyeShape
{
    public function path(): Path
    {
        return RoundedRect::path(-1.5, -1.5, 3, 3);
    }
}
