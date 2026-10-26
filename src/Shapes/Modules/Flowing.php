<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Modules;

use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Geometry\Transform;
use ExeQue\Qr\Rendering\ModuleContext;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\ModuleShape;

/**
 * Dark modules that melt together: neighbors join without a seam, outer corners are rounded
 * and, with `concave`, inner elbows get a fillet, so the code looks poured rather than cut.
 * A lone module at radius 0.5 is a circle; modules touching only at a corner do not join.
 */
final readonly class Flowing implements Configurable, ModuleShape
{
    use HasOptions;

    // A quarter circle as one cubic curve: the handle length for radius 1.
    private const float HANDLE = 0.5523;

    /**
     * @param float $radius Of the rounded corners, in modules from 0 to 0.5.
     * @param bool $concave Fillet the inside of elbows too.
     */
    public function __construct(
        public float $radius = 0.35,
        public bool $concave = true,
    ) {
        self::within('radius', $radius, 0.0, 0.5);
    }

    public function draw(ModuleContext $cell): Path
    {
        // A corner is rounded when both neighbors along its sides are light.
        $round = fn (int $dx, int $dy): float => ! $cell->isDark($dx, 0) && ! $cell->isDark(0, $dy) ? $this->radius : 0.0;

        $path = RoundedRect::path(-0.5, -0.5, 1, 1, [$round(1, -1), $round(1, 1), $round(-1, 1), $round(-1, -1)]);

        if (! $this->concave || $this->radius === 0.0) {
            return $path;
        }

        // Each elbow is filled once: by the dark module diagonally across the light one.
        foreach ([[1, -1], [1, 1], [-1, 1], [-1, -1]] as [$dx, $dy]) {
            if ($cell->isDark($dx, 0) && $cell->isDark(0, $dy) && ! $cell->isDark($dx, $dy)) {
                $path = $path->append($this->fillet($dx, $dy));
            }
        }

        return $path;
    }

    /**
     * The fillet in the corner of the light module at (dx, dy): a square of the radius at the
     * shared corner, less a quarter circle, mirrored from the bottom-right one.
     */
    private function fillet(int $dx, int $dy): Path
    {
        $radius = $this->radius;
        $handle = self::HANDLE * $radius;

        $fillet = Path::make()
            ->moveTo(0.5, 0.5)
            ->lineTo(0.5 + $radius, 0.5)
            ->curveTo(0.5 + $radius - $handle, 0.5, 0.5, 0.5 + $radius - $handle, 0.5, 0.5 + $radius)
            ->close()
            ->transform(Transform::identity()->scale($dx, $dy));

        // A mirror turns it counter-clockwise; keep every piece clockwise.
        return $dx * $dy < 0 ? $fillet->reversed() : $fillet;
    }
}
