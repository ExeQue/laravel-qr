<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\OuterEyes;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;

/**
 * The standard square ring.
 */
final readonly class Square implements OuterEyeShape
{
    public function path(): Path
    {
        return RoundedRect::path(-3.5, -3.5, 7, 7)->append(RoundedRect::path(-2.5, -2.5, 5, 5)->reversed());
    }
}
