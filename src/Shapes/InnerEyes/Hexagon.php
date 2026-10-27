<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;
use ExeQue\Qr\Shapes\Contracts\Symmetric;

/**
 * A regular hexagon, 3 modules across its flat sides, lying or standing like the hexagon ring; its
 * points reach a little past the 3x3 box.
 */
final readonly class Hexagon implements Configurable, InnerEyeShape, Symmetric
{
    use HasOptions;

    /**
     * @param bool $standing Points at the top and bottom rather than at the sides.
     */
    public function __construct(
        public bool $standing = false,
    ) {
    }

    public function path(): Path
    {
        return $this->standing
            ? Figures::hexagon(0, 0, 3, 3 * 2 / M_SQRT3, standing: true)
            : Figures::hexagon(0, 0, 3 * 2 / M_SQRT3, 3);
    }
}
