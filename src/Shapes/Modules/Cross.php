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
 * A plus across the whole cell.
 */
final readonly class Cross implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $width Of each bar, in modules.
     */
    public function __construct(
        public float $width = 0.4,
    ) {
        self::within('width', $width, 0.1, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        return Figures::cross(0, 0, 0.5, $this->width / 2);
    }
}
