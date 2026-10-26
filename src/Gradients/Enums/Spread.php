<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients\Enums;

/**
 * What a gradient does beyond its ends.
 */
enum Spread: string
{
    /** Keeps the end colors. */
    case Pad = 'pad';

    /** Starts over. */
    case Repeat = 'repeat';

    /** Runs back and forth. */
    case Reflect = 'reflect';
}
