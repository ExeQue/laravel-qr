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
 * A square with rounded corners, a little smaller than the cell.
 */
final readonly class Rounded implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $side In modules, from 0.1 to 1.
     * @param float $radius Of the corners, in modules.
     */
    public function __construct(
        public float $side = 0.92,
        public float $radius = 0.3,
    ) {
        self::within('side', $side, 0.1, 1.0);
        self::within('radius', $radius, 0.0, 0.5);
    }

    public function draw(ModuleContext $cell): Path
    {
        return RoundedRect::path(-$this->side / 2, -$this->side / 2, $this->side, $this->side, $this->radius);
    }
}
