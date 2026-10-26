<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder\Enums;

enum Mask: int
{
    case Pattern0 = 0;
    case Pattern1 = 1;
    case Pattern2 = 2;
    case Pattern3 = 3;
    case Pattern4 = 4;
    case Pattern5 = 5;
    case Pattern6 = 6;
    case Pattern7 = 7;

    /**
     * Whether the mask flips the cell at column `$x`, row `$y`
     * (ISO/IEC 18004:2015, table 10).
     */
    public function flips(int $x, int $y): bool
    {
        return match ($this) {
            self::Pattern0 => ($y + $x) % 2 === 0,
            self::Pattern1 => $y % 2 === 0,
            self::Pattern2 => $x % 3 === 0,
            self::Pattern3 => ($y + $x) % 3 === 0,
            self::Pattern4 => (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0,
            self::Pattern5 => ($y * $x) % 2 + ($y * $x) % 3 === 0,
            self::Pattern6 => (($y * $x) % 2 + ($y * $x) % 3) % 2 === 0,
            self::Pattern7 => (($y + $x) % 2 + ($y * $x) % 3) % 2 === 0,
        };
    }
}
