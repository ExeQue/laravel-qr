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
 * A pill standing up.
 */
final readonly class Pill implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $width In modules, from 0.2 to 1; it is the cell's height tall.
     */
    public function __construct(
        public float $width = 0.6,
    ) {
        self::within('width', $width, 0.2, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        return RoundedRect::path(-$this->width / 2, -0.48, $this->width, 0.96, $this->width / 2);
    }
}
