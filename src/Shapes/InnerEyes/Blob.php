<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\InnerEyes;

use ExeQue\Qr\Geometry\Figures;
use ExeQue\Qr\Geometry\Path;
use ExeQue\Qr\Shapes\Concerns\HasOptions;
use ExeQue\Qr\Shapes\Contracts\Configurable;
use ExeQue\Qr\Shapes\Contracts\InnerEyeShape;

/**
 * A round shape with a soft, uneven edge.
 */
final readonly class Blob implements Configurable, InnerEyeShape
{
    use HasOptions;

    /**
     * @param float $amount How far the edge strays, as a share of the radius, up to 0.15.
     * @param int $seed Another seed gives another shape.
     */
    public function __construct(
        public float $amount = 0.08,
        public int $seed = 1,
    ) {
        self::within('amount', $amount, 0.0, 0.15);
    }

    public function path(): Path
    {
        return Figures::blob(0, 0, 1.55, $this->amount, $this->seed);
    }
}
