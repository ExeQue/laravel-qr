<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\OuterEyes;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\Symmetric;

/**
 * A ghost's outline as a ring a module wide: a round head, straight sides and a hem in four scallops
 * that rise between their points, its inner edge a module above its outer one. A point sits at the
 * middle, so a line down it still measures seven modules. It floats upright on every corner: a
 * mirrored style leaves it as it is.
 */
final readonly class Ghost implements OuterEyeShape, Symmetric
{
    /**
     * How far the hem rises between its points, in modules.
     */
    private const float RISE = 0.45;

    public function path(): Path
    {
        // Each scallop is an arc through two points 1.75 apart, its middle RISE above them.
        $half = 0.875;
        $radius = ($half ** 2 + self::RISE ** 2) / (2 * self::RISE);
        $below = $radius - self::RISE;
        $point = rad2deg(atan2(-$below, $half));
        $angleAt = fn (float $across): float => rad2deg(atan2(-sqrt($radius ** 2 - $across ** 2), $across));

        $outline = Path::make()->arc(0, 0, 3.5, 180, 360)->lineTo(3.5, 3.5);

        foreach ([2.625, 0.875, -0.875, -2.625] as $middle) {
            $outline = $outline->arc($middle, 3.5 + $below, $radius, $point, -180 - $point);
        }

        // The hole's hem a module higher, from the left side to the right, the end scallops cut by the sides.
        $hem = 2.5 + $below;
        $hole = Path::make()->moveTo(-2.5, 0)
            ->arc(-2.625, $hem, $radius, $angleAt(0.125), $point)
            ->arc(-0.875, $hem, $radius, -180 - $point, $point)
            ->arc(0.875, $hem, $radius, -180 - $point, $point)
            ->arc(2.625, $hem, $radius, -180 - $point, $angleAt(-0.125))
            ->lineTo(2.5, 0)
            ->arc(0, 0, 2.5, 0, -180)
            ->close();

        return $outline->close()->append($hole);
    }
}
