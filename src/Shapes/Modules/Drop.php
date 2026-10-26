<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Concerns\PointsToACorner;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Enums\Direction;

/**
 * A drop: three corners fully round, one pointed - top-left unless told otherwise, or a
 * corner picked for each module.
 */
final readonly class Drop implements Configurable, ModuleShape
{
    use HasOptions;
    use PointsToACorner;

    /**
     * @param float $side In modules, from 0.1 to 1.
     * @param Direction $point The pointed corner; Random picks one for each module.
     * @param int $seed Another seed gives Random other corners.
     */
    public function __construct(
        public float $side = 0.94,
        public Direction $point = Direction::TopLeft,
        public int $seed = 0,
    ) {
        self::within('side', $side, 0.1, 1.0);
    }

    public function draw(ModuleContext $cell): Path
    {
        $half = $this->side / 2;

        return RoundedRect::path(-$half, -$half, $this->side, $this->side, self::radii($half, $this->corner($cell)));
    }
}
