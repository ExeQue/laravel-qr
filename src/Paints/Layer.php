<?php

declare(strict_types=1);

namespace ExeQue\Qr\Paints;

use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Exceptions\InvalidStyleException;

/**
 * One paint of a layered paint, with how much of it shows.
 */
final readonly class Layer
{
    /**
     * @param float $opacity 0-1, over the opacity the paint's colors already have.
     */
    public function __construct(
        public Paint $paint,
        public float $opacity = 1.0,
    ) {
        if ($opacity < 0 || $opacity > 1) {
            throw InvalidStyleException::opacityOutOfRange($opacity);
        }

        if ($paint instanceof Layered) {
            throw InvalidStyleException::nestedLayers();
        }
    }
}
