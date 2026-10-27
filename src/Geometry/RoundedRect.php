<?php

declare(strict_types=1);

namespace ExeQue\Qr\Geometry;

/**
 * A rectangle with a radius per corner, drawn clockwise. Each corner is a
 * circular arc of two cubic curves; a radius is held to half the shorter side.
 */
final class RoundedRect
{
    /**
     * @param float|array{float, float, float, float} $radius One for every corner, or top-right, bottom-right, bottom-left, top-left.
     */
    public static function path(float $x, float $y, float $width, float $height, float|array $radius = 0.0): Path
    {
        [$topRight, $bottomRight, $bottomLeft, $topLeft] = array_map(
            fn (float $corner): float => max(0.0, min($corner, $width / 2, $height / 2)),
            is_array($radius) ? $radius : [$radius, $radius, $radius, $radius],
        );

        [$right, $bottom] = [$x + $width, $y + $height];

        $path = Path::make()->moveTo($x + $topLeft, $y)->lineTo($right - $topRight, $y);
        $path = self::corner($path, $right - $topRight, $y + $topRight, $topRight, -90);
        $path = $path->lineTo($right, $bottom - $bottomRight);
        $path = self::corner($path, $right - $bottomRight, $bottom - $bottomRight, $bottomRight, 0);
        $path = $path->lineTo($x + $bottomLeft, $bottom);
        $path = self::corner($path, $x + $bottomLeft, $bottom - $bottomLeft, $bottomLeft, 90);
        $path = $path->lineTo($x, $y + $topLeft);
        $path = self::corner($path, $x + $topLeft, $y + $topLeft, $topLeft, 180);

        return $path->close();
    }

    /**
     * A circle, drawn clockwise.
     */
    public static function circle(float $centerX, float $centerY, float $radius): Path
    {
        return self::path($centerX - $radius, $centerY - $radius, $radius * 2, $radius * 2, $radius);
    }

    /**
     * A quarter circle clockwise from `$startAngle` (degrees, 0 pointing right, y down).
     */
    private static function corner(Path $path, float $centerX, float $centerY, float $radius, float $startAngle): Path
    {
        return $radius === 0.0 ? $path : $path->arc($centerX, $centerY, $radius, $startAngle, $startAngle + 90);
    }
}
