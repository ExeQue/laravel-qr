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
 * A regular hexagon, lying (flat along the top and bottom) or standing (a point up).
 */
final readonly class Hexagon implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $radius From the middle to each corner, in modules.
     * @param bool $standing A point up rather than a flat side.
     */
    public function __construct(
        public float $radius = 0.5,
        public bool $standing = false,
    ) {
        self::within('radius', $radius, 0.1, 0.6);
    }

    public function draw(ModuleContext $cell): Path
    {
        [$across, $flats] = [2 * $this->radius, $this->radius * M_SQRT3];

        return $this->standing
            ? Figures::hexagon(0, 0, $flats, $across, standing: true)
            : Figures::hexagon(0, 0, $across, $flats);
    }
}
