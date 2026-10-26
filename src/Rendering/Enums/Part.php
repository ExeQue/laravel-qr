<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering\Enums;

/**
 * What a drawn item is.
 */
enum Part: string
{
    case Module = 'module';
    case OuterEye = 'outer-eye';
    case InnerEye = 'inner-eye';
}
