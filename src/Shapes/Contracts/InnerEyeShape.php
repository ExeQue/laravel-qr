<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

use ExeQue\Qr\Geometry\Path;

/**
 * The 3x3 middle of a finder pattern, in a box from -1.5 to 1.5, centerd on (0, 0).
 *
 * It must measure about 3 modules across both ways and have its weight on the middle.
 */
interface InnerEyeShape
{
    public function path(): Path;
}
