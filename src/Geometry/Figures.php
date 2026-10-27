<?php

declare(strict_types=1);

namespace ExeQue\Qr\Geometry;

/**
 * Closed figures for shapes, drawn clockwise about a middle point, in modules (y grows downward).
 */
final class Figures
{
    /**
     * A superellipse, |x|^4 + |y|^4 = 1, filling the box: a square with soft, full sides.
     */
    public static function squircle(float $x, float $y, float $width, float $height): Path
    {
        // A curve per quarter: its handles, 0.909 of the way along each side, put its middle on
        // the superellipse.
        [$w, $h, $k] = [$width / 2, $height / 2, 0.909];

        return Path::make()
            ->moveTo($x + $w, $y)
            ->curveTo($x + $w, $y + $k * $h, $x + $k * $w, $y + $h, $x, $y + $h)
            ->curveTo($x - $k * $w, $y + $h, $x - $w, $y + $k * $h, $x - $w, $y)
            ->curveTo($x - $w, $y - $k * $h, $x - $k * $w, $y - $h, $x, $y - $h)
            ->curveTo($x + $k * $w, $y - $h, $x + $w, $y - $k * $h, $x + $w, $y)
            ->close();
    }

    public static function heart(float $x, float $y, float $scale, float $lean = 0.0, float $shift = 0.0): Path
    {
        $heart = Path::make()
            ->moveTo(0, -0.55)
            ->curveTo(0, -1.45, -1.65, -1.75, -1.65, -0.35)
            ->curveTo(-1.65, 0.55, -0.6, 1.05, 0, 1.65)
            ->curveTo(0.6, 1.05, 1.65, 0.55, 1.65, -0.35)
            ->curveTo(1.65, -1.75, 0, -1.45, 0, -0.55)
            ->close()
            // Drawn counter-clockwise above; every figure here is clockwise.
            ->reversed();

        return $heart->transform(Transform::identity()->translate(0, $shift)->rotate($lean)->scale($scale)->translate($x, $y));
    }

    /**
     * A star of `$points` points, corners alternating between the two radii, the first pointing up
     * and turned by `$turn` degrees. With equal radii and three points: a hexagon, point up.
     */
    public static function star(float $x, float $y, float $outer, float $inner, int $points, float $turn = 0.0): Path
    {
        $corners = [];

        for ($corner = 0; $corner < 2 * $points; $corner++) {
            $angle = deg2rad(-90 + $turn + 180 / $points * $corner);
            $radius = $corner % 2 === 0 ? $outer : $inner;

            $corners[] = [$x + $radius * cos($angle), $y + $radius * sin($angle)];
        }

        return Path::polygon($corners);
    }

    /**
     * A box with its corners cut off `$cut` along each side; regular at `$width / (2 + √2)`.
     */
    public static function octagon(float $x, float $y, float $width, float $height, float $cut): Path
    {
        [$left, $top, $right, $bottom] = [$x - $width / 2, $y - $height / 2, $x + $width / 2, $y + $height / 2];

        return Path::polygon([
            [$left + $cut, $top], [$right - $cut, $top], [$right, $top + $cut], [$right, $bottom - $cut],
            [$right - $cut, $bottom], [$left + $cut, $bottom], [$left, $bottom - $cut], [$left, $top + $cut],
        ]);
    }

    /**
     * A hexagon filling the box: lying, flat along the top and bottom with its points at the middle of
     * the sides; or standing, flat along the sides with its points at the middle of the top and bottom.
     */
    public static function hexagon(float $x, float $y, float $width, float $height, bool $standing = false): Path
    {
        if ($standing) {
            [$half, $quarter, $rise] = [$height / 2, $height / 4, $width / 2];

            return Path::polygon([
                [$x, $y - $half], [$x + $rise, $y - $quarter], [$x + $rise, $y + $quarter],
                [$x, $y + $half], [$x - $rise, $y + $quarter], [$x - $rise, $y - $quarter],
            ]);
        }

        [$half, $quarter, $rise] = [$width / 2, $width / 4, $height / 2];

        return Path::polygon([
            [$x - $quarter, $y - $rise], [$x + $quarter, $y - $rise], [$x + $half, $y],
            [$x + $quarter, $y + $rise], [$x - $quarter, $y + $rise], [$x - $half, $y],
        ]);
    }

    /**
     * A square on its corner, `$radius` from the middle to each corner.
     */
    public static function diamond(float $x, float $y, float $radius): Path
    {
        return Path::polygon([[$x, $y - $radius], [$x + $radius, $y], [$x, $y + $radius], [$x - $radius, $y]]);
    }

    /**
     * A square of `$side`, turned `$degrees` about its middle.
     */
    public static function turnedSquare(float $x, float $y, float $side, float $degrees): Path
    {
        return RoundedRect::path(-$side / 2, -$side / 2, $side, $side)
            ->transform(Transform::identity()->rotate($degrees)->translate($x, $y));
    }

    /**
     * One outline around a heart circle and `$petals` petal circles, `$reach` from the middle, the first up:
     * the arcs of each that lie outside the others.
     */
    public static function flower(float $x, float $y, float $petal, float $reach, float $heart, int $petals = 6): Path
    {
        $half = 180 / $petals;
        $petalAt = fn (int $index): float => -90 + 2 * $half * $index;
        $edge = fn (float $degrees, float $distance): array => [$x + $distance * cos(deg2rad($degrees)), $y + $distance * sin(deg2rad($degrees))];
        $angleOn = fn (array $point, array $center): float => rad2deg(atan2($point[1] - $center[1], $point[0] - $center[0]));

        // Neighboring petals meet on the line between them, unless the heart reaches past where they do.
        $along = $reach * cos(deg2rad($half));
        $left = $petal ** 2 - $reach ** 2 + $along ** 2;
        $meet = $left >= 0 ? $along + sqrt($left) : null;

        // Otherwise each petal leaves the heart this many degrees to either side of its own line.
        $cos = $reach > 0 && $heart > 0 ? ($reach ** 2 + $heart ** 2 - $petal ** 2) / (2 * $reach * $heart) : null;

        if ($meet !== null && $meet >= $heart) {
            [$from, $to, $distance, $heartShows] = [-$half, $half, $meet, false];
        } elseif ($cos !== null && abs($cos) <= 1) {
            [$from, $to, $distance, $heartShows] = [-rad2deg(acos($cos)), rad2deg(acos($cos)), $heart, true];
        } else {
            // A heart over every petal, or petals apart from it and each other: the circles as they are.
            return Path::merge([
                RoundedRect::circle($x, $y, $heart),
                ...array_map(fn (int $index): Path => RoundedRect::circle(...[...$edge($petalAt($index), $reach), $petal]), range(0, $petals - 1)),
            ]);
        }

        $path = Path::make();

        for ($index = 0; $index < $petals; $index++) {
            $center = $edge($petalAt($index), $reach);
            $start = $angleOn($edge($petalAt($index) + $from, $distance), $center);
            $end = $angleOn($edge($petalAt($index) + $to, $distance), $center);
            $path = $path->arc($center[0], $center[1], $petal, $start, $end > $start ? $end : $end + 360);

            if ($heartShows) {
                $path = $path->arc($x, $y, $heart, $petalAt($index) + $to, $petalAt($index + 1) + $from);
            }
        }

        return $path->close();
    }

    public static function snowflake(float $x, float $y, float $reach, float $width): Path
    {
        $points = [];

        for ($arm = 0; $arm < 6; $arm++) {
            $angle = deg2rad(-90 + 60 * $arm);
            $between = $angle - deg2rad(30);
            [$dx, $dy] = [cos($angle), sin($angle)];
            [$nx, $ny] = [-$dy, $dx];

            $points[] = [$x + $width * cos($between), $y + $width * sin($between)];
            $points[] = [$x + $reach * $dx - $width / 2 * $nx, $y + $reach * $dy - $width / 2 * $ny];
            $points[] = [$x + $reach * $dx + $width / 2 * $nx, $y + $reach * $dy + $width / 2 * $ny];
        }

        return Path::polygon($points);
    }

    /**
     * A four-pointed sparkle: points on the axes, `$radius` out, its sides curving in toward the
     * middle; `$pinch` (0 to 1) is how full the sides stay.
     */
    public static function sparkle(float $x, float $y, float $radius, float $pinch): Path
    {
        [$far, $near] = [$radius * $pinch, $radius * $pinch * 0.3];

        $sparkle = Path::make()->moveTo(0, -$radius)
            ->curveTo($near, -$far, $far, -$near, $radius, 0)
            ->curveTo($far, $near, $near, $far, 0, $radius)
            ->curveTo(-$near, $far, -$far, $near, -$radius, 0)
            ->curveTo(-$far, -$near, -$near, -$far, 0, -$radius)
            ->close();

        return $sparkle->transform(Transform::identity()->translate($x, $y));
    }

    /**
     * A plus: two bars across each other, `$half` out from the middle and `$arm` wide each way.
     */
    public static function cross(float $x, float $y, float $half, float $arm): Path
    {
        $points = [
            [-$arm, -$half], [$arm, -$half], [$arm, -$arm], [$half, -$arm], [$half, $arm], [$arm, $arm],
            [$arm, $half], [-$arm, $half], [-$arm, $arm], [-$half, $arm], [-$half, -$arm], [-$arm, -$arm],
        ];

        return Path::polygon(array_map(fn (array $point): array => [$x + $point[0], $y + $point[1]], $points));
    }

    /**
     * Four leaves around a middle: circles of `$leaf` set `$offset` out on the diagonals, and one of
     * `$heart` in the middle, drawn together.
     */
    public static function clover(float $x, float $y, float $offset, float $leaf, float $heart): Path
    {
        $leaves = array_map(fn (array $corner): Path => RoundedRect::circle($x + $corner[0] * $offset, $y + $corner[1] * $offset, $leaf), [[-1, -1], [1, -1], [1, 1], [-1, 1]]);

        return Path::merge([...$leaves, RoundedRect::circle($x, $y, $heart)]);
    }

    /**
     * A round shape with a soft, uneven edge: `$amount` is how far the edge strays, as a share of the
     * radius; `$seed` picks the unevenness.
     */
    public static function blob(float $x, float $y, float $radius, float $amount, int $seed = 0): Path
    {
        $points = [];

        for ($step = 0; $step < 64; $step++) {
            $angle = 2 * M_PI * $step / 64;
            $edge = $radius * (1 + $amount * (0.6 * sin(3 * $angle + $seed) + 0.4 * cos(5 * $angle + 2 * $seed)));
            $points[] = [$x + $edge * cos($angle), $y + $edge * sin($angle)];
        }

        return Path::polygon($points);
    }

    /**
     * A square whose sides are scalloped: `$bumps` arches along each, dipping `$depth` inwards between them.
     */
    public static function scalloped(float $x, float $y, float $size, float $depth, int $bumps): Path
    {
        $half = $size / 2;
        $corners = [[-$half, -$half], [$half, -$half], [$half, $half], [-$half, $half]];
        $points = [];

        foreach ($corners as $index => [$x1, $y1]) {
            [$x2, $y2] = $corners[($index + 1) % 4];
            // The side's inward normal, for a square drawn clockwise.
            [$nx, $ny] = [-($y2 - $y1) / $size, ($x2 - $x1) / $size];

            for ($step = 0; $step < $bumps * 8; $step++) {
                $along = $step / ($bumps * 8);
                $in = $depth * (1 - abs(sin(M_PI * $bumps * $along)));
                $points[] = [$x + $x1 + ($x2 - $x1) * $along + $nx * $in, $y + $y1 + ($y2 - $y1) * $along + $ny * $in];
            }
        }

        return Path::polygon($points);
    }
}
