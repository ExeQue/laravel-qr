<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

/**
 * Groups of modules with their top-left and bottom-right outer corners rounded, the others square.
 */
final readonly class Classy implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $radius Of the rounded corners, in modules, from 0 to 0.5.
     */
    public function __construct(
        public float $radius = 0.5,
    ) {
        self::within('radius', $radius, 0.0, 0.5);
    }

    public function draw(ModuleContext $cell): Path
    {
        $topLeft = ! $cell->isDark(-1, 0) && ! $cell->isDark(0, -1) ? $this->radius : 0.0;
        $bottomRight = ! $cell->isDark(1, 0) && ! $cell->isDark(0, 1) ? $this->radius : 0.0;

        return RoundedRect::path(-0.5, -0.5, 1, 1, [0, $bottomRight, 0, $topLeft]);
    }
}
