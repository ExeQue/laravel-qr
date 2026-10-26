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
 * A six-armed snowflake.
 */
final readonly class Snowflake implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $reach From the middle to each arm's end, in modules.
     * @param float $width Of each arm.
     */
    public function __construct(
        public float $reach = 0.52,
        public float $width = 0.26,
    ) {
        self::within('reach', $reach, 0.1, 0.75);
        self::within('width', $width, 0.05, 0.5);
    }

    public function draw(ModuleContext $cell): Path
    {
        return Figures::snowflake(0, 0, $this->reach, $this->width);
    }
}
