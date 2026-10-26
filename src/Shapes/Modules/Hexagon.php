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
 * A hexagon, point up.
 */
final readonly class Hexagon implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $radius From the middle to each corner, in modules.
     */
    public function __construct(
        public float $radius = 0.5,
    ) {
        self::within('radius', $radius, 0.1, 0.6);
    }

    public function draw(ModuleContext $cell): Path
    {
        return Figures::star(0, 0, $this->radius, $this->radius, 3);
    }
}
