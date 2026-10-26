<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\Directional;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A block with rounded corners: one radius for every corner, or four, top-right, bottom-right,
 * bottom-left, top-left. At 1.5 all round, a circle.
 */
final readonly class Rounded implements Configurable, Directional, InnerEyeShape
{
    use HasOptions;

    /**
     * @param float|array{float, float, float, float} $radius Up to 1.5.
     */
    public function __construct(
        public float|array $radius = 0.7,
    ) {
    }

    public function path(): Path
    {
        return RoundedRect::path(-1.5, -1.5, 3, 3, $this->radius);
    }

    /**
     * Corners rounded unlike each other, as round-corner, drop and leaf have: turned on each
     * corner so they point out the same way.
     */
    public function isDirectional(): bool
    {
        return is_array($this->radius) && count(array_unique(array_map(strval(...), $this->radius))) > 1;
    }
}
