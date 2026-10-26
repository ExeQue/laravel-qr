<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

/**
 * A star, moved down a little so it sits square in the cell.
 */
final readonly class Star implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $outer The points' radius, in modules.
     * @param float $inner The radius between the points.
     */
    public function __construct(
        public float $outer = 0.52,
        public float $inner = 0.34,
        public int $points = 5,
    ) {
        self::within('points', $points, 3, 12);
    }

    public function draw(ModuleContext $cell): Path
    {
        // The lowest points stop short of the radius; split the difference.
        $drop = $this->outer * (1 - cos(M_PI / $this->points)) / 2;

        return Figures::star(0, $drop, $this->outer, $this->inner, $this->points);
    }
}
