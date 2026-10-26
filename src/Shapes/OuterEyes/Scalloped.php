<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\OuterEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Geometry\RoundedRect;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;

/**
 * A ring with scalloped sides, round arches along each side, and a softly rounded hole.
 */
final readonly class Scalloped implements Configurable, OuterEyeShape
{
    use HasOptions;

    /**
     * @param float $depth How far the outline dips between arches, in modules, up to 0.25.
     * @param int $bumps Arches along each side.
     */
    public function __construct(
        public float $depth = 0.15,
        public int $bumps = 4,
    ) {
        self::within('depth', $depth, 0.0, 0.25);
        self::within('bumps', $bumps, 2, 8);
    }

    public function path(): Path
    {
        return Figures::scalloped(0, 0, 7, $this->depth, $this->bumps)
            ->append(RoundedRect::path(-2.5, -2.5, 5, 5, 0.6)->reversed());
    }
}
