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
 * Little ghosts: a round head and a hem in three scallops.
 */
final readonly class Ghost implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $size The ghost's width and height, in modules.
     */
    public function __construct(
        public float $size = 0.92,
    ) {
        self::within('size', $size, 0.6, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        return Figures::ghost(0, 0, $this->size, $this->size);
    }
}
