<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Concerns;

use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Enums\Direction;
use ExeQue\Qr\Support\Seed;

/**
 * For module shapes with a `$point` and a `$seed`: the corner a module points to, a random
 * one picked from the module's seed the same way on every render.
 *
 * @internal
 */
trait PointsToACorner
{
    private function corner(ModuleContext $cell): Direction
    {
        return $this->point === Direction::Random
            ? Seed::pick($cell->seed, Direction::corners(), purpose: static::class.":{$this->seed}")
            : $this->point;
    }

    /**
     * Every corner at `$radius` but the pointed ones, in a rounded rectangle's order.
     *
     * @return array{float, float, float, float}
     */
    private static function radii(float $radius, Direction ...$pointed): array
    {
        $round = fn (int $index): float => in_array($index, array_map(fn (Direction $corner): int => $corner->radiusIndex(), $pointed), true) ? 0.0 : $radius;

        return [$round(0), $round(1), $round(2), $round(3)];
    }
}
