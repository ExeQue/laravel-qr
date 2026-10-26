<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients;

use ExeQue\Qr\Colors\Color;
use ExeQue\Qr\Contracts\Color as ColorContract;
use ExeQue\Qr\Exceptions\InvalidGradientException;

final readonly class ColorStop
{
    public ColorContract $color;

    /**
     * @param float $position Along the gradient, 0-1.
     * @param float|null $hint Where, between this stop and the next, the colors meet halfway (a CSS color hint).
     */
    public function __construct(
        ColorContract|string $color,
        public float $position,
        public ?float $hint = null,
    ) {
        if ($position < 0 || $position > 1) {
            throw InvalidGradientException::positionOutOfRange($position);
        }

        $this->color = Color::make($color);
    }
}
