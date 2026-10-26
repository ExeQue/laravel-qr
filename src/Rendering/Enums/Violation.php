<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering\Enums;

/**
 * What happens when a code is unlikely to scan.
 */
enum Violation: string
{
    case Throw = 'throw';
    case Warn = 'warn';
}
