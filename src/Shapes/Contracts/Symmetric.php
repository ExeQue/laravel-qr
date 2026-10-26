<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

/**
 * An eye part that is its own mirror image on every corner, so mirrored() leaves it as it is
 * rather than turning it, which would stand a lying hexagon up.
 */
interface Symmetric
{
}
