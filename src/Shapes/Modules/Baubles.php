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
 * Christmas baubles: a round bead with a small cap on top.
 */
final readonly class Baubles implements Configurable, ModuleShape
{
    use HasOptions;

    /**
     * @param float $diameter The bauble's, in modules; its cap sits above it.
     */
    public function __construct(
        public float $diameter = 0.8,
    ) {
        self::within('diameter', $diameter, 0.5, 0.86);
    }

    public function draw(ModuleContext $cell): Path
    {
        return self::bauble($this->diameter);
    }

    /**
     * A bauble `$diameter` across, sitting at the bottom of the cell, its cap at the top.
     */
    public static function bauble(float $diameter): Path
    {
        $radius = $diameter / 2;
        $middle = 0.5 - $radius;

        return RoundedRect::circle(0, $middle, $radius)
            ->append(RoundedRect::path(-0.12, $middle - $radius - 0.12, 0.24, 0.16, 0.03));
    }
}
