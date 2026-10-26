<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters\Enums;

/**
 * A module property that patterned filters vary with position.
 */
enum Property: string
{
    /** Degrees, added. */
    case Rotation = 'rotation';

    /** A factor, multiplied. */
    case Size = 'size';

    /** A factor, multiplied. */
    case Opacity = 'opacity';
}
