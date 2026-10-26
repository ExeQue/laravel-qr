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
 * Dots that grow with their company: a lone module small, one with four neighbors large.
 */
final readonly class Bubbles implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $smallest A lone dot's diameter, in modules.
     * @param float $largest The diameter with four neighbors, in modules.
     */
    public function __construct(
        public float $smallest = 0.55,
        public float $largest = 0.95,
    ) {
        self::within('smallest', $smallest, 0.2, 1.0);
        self::within('largest', $largest, $smallest, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        $neighbors = count(array_filter([$cell->isDark(1, 0), $cell->isDark(-1, 0), $cell->isDark(0, 1), $cell->isDark(0, -1)]));

        return RoundedRect::circle(0, 0, ($this->smallest + ($this->largest - $this->smallest) * $neighbors / 4) / 2);
    }
}
