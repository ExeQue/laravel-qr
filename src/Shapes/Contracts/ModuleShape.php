<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Rendering\ModuleContext;

/**
 * Draws one dark cell, about its middle (0, 0), in modules. It should stay
 * within -0.5 to 0.5 and cover the middle well: readers sample a cell there.
 */
interface ModuleShape
{
    public function draw(ModuleContext $cell): Path;
}
