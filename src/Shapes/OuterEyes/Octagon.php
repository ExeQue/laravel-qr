<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\OuterEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\OuterEyeShape;

/**
 * A ring with its corners cut off.
 */
final readonly class Octagon implements Configurable, OuterEyeShape
{
    use HasOptions;

    /**
     * @param float $cut How far along each side the outline's corners are cut, in modules.
     * @param float $holeCut The same for the hole.
     */
    public function __construct(
        public float $cut = 1.7,
        public float $holeCut = 1.3,
    ) {
        self::within('cut', $cut, 0.0, 3.0);
        self::within('holeCut', $holeCut, 0.0, 2.0);

        // Across a cut corner the ring is thinner by (cut - holeCut) / √2; keep at least half a module.
        self::within('cut', $cut, 0.0, $holeCut + 2 - 0.5 * M_SQRT2);
    }

    public function path(): Path
    {
        return Figures::octagon(0, 0, 7, 7, $this->cut)->append(Figures::octagon(0, 0, 5, 5, $this->holeCut)->reversed());
    }
}
