<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

/**
 * An eye part with one corner unlike the others, drawn with that corner at the top left. It is
 * turned on each corner of the code so that corner points out: a quarter turn on the top right,
 * a quarter back on the bottom left.
 */
interface Directional
{
    /**
     * Whether this part, as configured, has such a corner.
     */
    public function isDirectional(): bool;
}
