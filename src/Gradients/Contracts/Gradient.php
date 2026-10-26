<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients\Contracts;

use ExeQue\Qr\Contracts\Color;
use ExeQue\Qr\Contracts\Paint;
use ExeQue\Qr\Gradients\ColorStop;

interface Gradient extends Paint
{
    /**
     * The color at a point of the gradient's box, 0-1 on both axes, y downwards.
     */
    public function colorAt(float $x, float $y): Color;

    /**
     * @return list<ColorStop>
     */
    public function stops(): array;
}
