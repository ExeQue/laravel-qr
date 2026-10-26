<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A block with its corners cut off; regular by default.
 */
final readonly class Octagon implements Configurable, InnerEyeShape
{
    use HasOptions;

    /**
     * @param float $cut How far along each side the corners are cut, in modules.
     */
    public function __construct(
        public float $cut = 0.88,
    ) {
        self::within('cut', $cut, 0.0, 1.4);
    }

    public function path(): Path
    {
        return Figures::octagon(0, 0, 3, 3, $this->cut);
    }
}
