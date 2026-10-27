<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\OuterEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;
use ExeQue\Qr\Shapes\Contracts\Symmetric;

/**
 * A regular hexagonal ring, 7 modules across its flat sides: lying, flat along the top and bottom,
 * or standing, flat along the sides. Its points reach half a module past the box, into the light
 * separator around the eye, so the ring still measures seven modules across the middle both ways.
 */
final readonly class Hexagon implements Configurable, OuterEyeShape, Symmetric
{
    use HasOptions;

    /**
     * @param bool $standing Points at the top and bottom rather than at the sides.
     */
    public function __construct(
        public bool $standing = false,
    ) {
    }

    public function path(): Path
    {
        return self::hexagon(7, $this->standing)->append(self::hexagon(5, $this->standing)->reversed());
    }

    /**
     * A hexagon `$flats` across its flat sides.
     */
    private static function hexagon(float $flats, bool $standing): Path
    {
        $points = $flats * 2 / M_SQRT3;

        return $standing ? Figures::hexagon(0, 0, $flats, $points, standing: true) : Figures::hexagon(0, 0, $points, $flats);
    }
}
