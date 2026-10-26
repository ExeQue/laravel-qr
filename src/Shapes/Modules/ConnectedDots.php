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
 * Dots joined to their neighbors by narrow bridges, like a circuit board or a metro map.
 */
final readonly class ConnectedDots implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $diameter Of each dot, in modules, from 0.3 to 1.
     * @param float $bridge The width of the bridges, in modules, no wider than a dot.
     */
    public function __construct(
        public float $diameter = 0.72,
        public float $bridge = 0.26,
    ) {
        self::within('diameter', $diameter, 0.3, 1.0);
        self::within('bridge', $bridge, 0.05, $diameter);
    }

    public function draw(ModuleContext $cell): Path
    {
        $path = RoundedRect::circle(0, 0, $this->diameter / 2);
        $half = $this->bridge / 2;

        // Each bridge is drawn once: by the module to the left of it, or above it.
        if ($cell->isDark(1, 0)) {
            $path = $path->append(RoundedRect::path(0, -$half, 1, $this->bridge));
        }

        if ($cell->isDark(0, 1)) {
            $path = $path->append(RoundedRect::path(-$half, 0, $this->bridge, 1));
        }

        return $path;
    }
}
