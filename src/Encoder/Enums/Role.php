<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder\Enums;

/**
 * What a cell of the symbol is for.
 */
enum Role: string
{
    case Finder = 'finder';
    case Separator = 'separator';
    case Timing = 'timing';
    case Alignment = 'alignment';
    case Format = 'format';
    case Version = 'version';
    case DarkModule = 'dark-module';
    case Data = 'data';
}
