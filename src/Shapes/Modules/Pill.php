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
 * A pill standing up, or lying down.
 */
final readonly class Pill implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $width Across the pill, in modules, from 0.2 to 1; it is the cell long: its height when lying down.
     * @param bool $vertical Standing up rather than lying down.
     */
    public function __construct(
        public float $width = 0.6,
        public bool $vertical = true,
    ) {
        self::within('width', $width, 0.2, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        return $this->vertical
            ? RoundedRect::path(-$this->width / 2, -0.48, $this->width, 0.96, $this->width / 2)
            : RoundedRect::path(-0.48, -$this->width / 2, 0.96, $this->width, $this->width / 2);
    }
}
