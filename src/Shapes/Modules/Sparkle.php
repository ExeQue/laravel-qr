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
 * A four-pointed sparkle, its sides curving in.
 */
final readonly class Sparkle implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $radius From the middle to each point, in modules.
     * @param float $pinch How full the sides stay, from 0.1 (thin) to 1.
     */
    public function __construct(
        public float $radius = 0.56,
        public float $pinch = 0.32,
    ) {
        self::within('radius', $radius, 0.2, 0.6);
        self::within('pinch', $pinch, 0.1, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        return Figures::sparkle(0, 0, $this->radius, $this->pinch);
    }
}
