<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

/**
 * A four-leaf clover.
 */
final readonly class Clover implements ModuleShape
{
    public function draw(ModuleContext $cell): Path
    {
        return Figures::clover(0, 0, 0.2, 0.25, 0.2);
    }
}
