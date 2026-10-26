<?php

declare(strict_types=1);

namespace ExeQue\Qr\Gradients\Enums;

/**
 * What a gradient's 0-1 box covers.
 */
enum GradientBox: string
{
    /** The symbol, without the quiet zone. */
    case Code = 'code';

    /** The whole image, quiet zone included. */
    case Canvas = 'canvas';

    /** Each eye or module on its own. */
    case Shape = 'shape';
}
