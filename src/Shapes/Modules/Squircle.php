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
 * A squircle: a square with soft, full sides.
 */
final readonly class Squircle implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $side In modules, from 0.1 to 1.
     */
    public function __construct(
        public float $side = 0.94,
    ) {
        self::within('side', $side, 0.1, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        return Figures::squircle(0, 0, $this->side, $this->side);
    }
}
