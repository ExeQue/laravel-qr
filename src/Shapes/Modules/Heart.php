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
 * A heart, its weight moved onto the middle of the cell.
 */
final readonly class Heart implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $height In modules, from 0.1 to 1.2.
     */
    public function __construct(
        public float $height = 1.0,
    ) {
        self::within('height', $height, 0.1, 1.2);
    }

    public function draw(ModuleContext $cell): Path
    {
        return Figures::heart(0, 0, $this->height / 3.4, shift: 0.3);
    }
}
