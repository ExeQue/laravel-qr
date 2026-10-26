<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A square on its corner, reaching a little past the 3x3 box so a line across the middle
 * still measures about three modules.
 */
final readonly class Diamond implements InnerEyeShape
{
    public function path(): Path
    {
        return Figures::diamond(0, 0, 1.7);
    }
}
