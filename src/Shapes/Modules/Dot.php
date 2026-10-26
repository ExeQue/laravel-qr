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
 * A round dot.
 */
final readonly class Dot implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $diameter In modules, from 0.1 to 1.
     */
    public function __construct(
        public float $diameter = 0.8,
    ) {
        self::within('diameter', $diameter, 0.1, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        return RoundedRect::circle(0, 0, $this->diameter / 2);
    }
}
