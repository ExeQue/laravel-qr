<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

/**
 * The whole cell.
 */
final readonly class Square implements ModuleShape
{
    public function draw(ModuleContext $cell): Path
    {
        return RoundedRect::path(-0.5, -0.5, 1, 1);
    }
}
