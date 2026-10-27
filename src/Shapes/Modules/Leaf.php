<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Concerns\PointsToACorner;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;
use ExeQue\Qr\Shapes\Enums\Direction;

/**
 * A leaf: two opposite corners fully round, the other two pointed - top-left and bottom-right,
 * or top-right and bottom-left, or picked for each module.
 */
final readonly class Leaf implements Configurable, ModuleShape
{
    use HasOptions;
    use PointsToACorner;

    private const array POINTS = [Direction::TopLeft, Direction::TopRight, Direction::Random];

    /**
     * @param float $side In modules, from 0.1 to 1.
     * @param Direction $point The pointed top corner (and the one opposite): top-left or top-right; Random picks one for each module.
     * @param int $seed Another seed gives Random other corners.
     */
    public function __construct(
        public float $side = 0.94,
        public Direction $point = Direction::TopLeft,
        public int $seed = 0,
    ) {
        self::within('side', $side, 0.1, 1.0);

        // A leaf pointed bottom-right is the one pointed top-left.
        if (! in_array($point, self::POINTS, true)) {
            throw InvalidOptionException::notOneOf(self::class, 'point', array_map(fn (Direction $point): string => $point->value, self::POINTS), $point->value);
        }
    }

    public function draw(ModuleContext $cell): Path
    {
        $half = $this->side / 2;

        $pointed = $this->corner($cell, [Direction::TopLeft, Direction::TopRight]);

        return RoundedRect::path(-$half, -$half, $this->side, $this->side, self::radii($half, $pointed, $pointed->opposite()));
    }
}
