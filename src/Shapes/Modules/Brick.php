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
 * A brick: the cell's full width, with a joint above and below, so rows read as courses; or,
 * vertical, its full height, so columns do.
 */
final readonly class Brick implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $height Across the courses, in modules, from 0.3 to 1: its width when vertical.
     * @param bool $vertical Courses running down rather than across.
     */
    public function __construct(
        public float $height = 0.76,
        public bool $vertical = false,
    ) {
        self::within('height', $height, 0.3, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        return $this->vertical
            ? RoundedRect::path(-$this->height / 2, -0.5, $this->height, 1, 0.06)
            : RoundedRect::path(-0.5, -$this->height / 2, 1, $this->height, 0.06);
    }
}
