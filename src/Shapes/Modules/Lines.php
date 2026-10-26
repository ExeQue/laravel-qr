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
 * Modules joined one way only, into lines with round ends: down the code, or across it.
 */
final readonly class Lines implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param bool $vertical Lines down the code; across it when false.
     * @param float $width Of each line, in modules, from 0.2 to 1.
     */
    public function __construct(
        public bool $vertical = true,
        public float $width = 0.72,
    ) {
        self::within('width', $width, 0.2, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        $round = $this->width / 2;

        if ($this->vertical) {
            [$above, $below] = [$cell->isDark(0, -1) ? 0 : $round, $cell->isDark(0, 1) ? 0 : $round];

            return RoundedRect::path(-$round, -0.5, $this->width, 1, [$above, $below, $below, $above]);
        }

        [$before, $after] = [$cell->isDark(-1, 0) ? 0 : $round, $cell->isDark(1, 0) ? 0 : $round];

        return RoundedRect::path(-0.5, -$round, 1, $this->width, [$after, $after, $before, $before]);
    }
}
