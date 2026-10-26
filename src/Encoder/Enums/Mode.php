<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder\Enums;

enum Mode: string
{
    case Numeric = 'numeric';
    case Alphanumeric = 'alphanumeric';
    case Byte = 'byte';

    /**
     * The four-bit mode indicator (ISO/IEC 18004:2015, table 2).
     *
     * @internal
     */
    public function indicator(): int
    {
        return match ($this) {
            self::Numeric => 0b0001,
            self::Alphanumeric => 0b0010,
            self::Byte => 0b0100,
        };
    }

    /**
     * Bits in the character count indicator (ISO/IEC 18004:2015, table 3).
     *
     * @internal
     */
    public function countBits(int $version): int
    {
        $band = match (true) {
            $version <= 9 => 0,
            $version <= 26 => 1,
            default => 2,
        };

        return match ($this) {
            self::Numeric => [10, 12, 14][$band],
            self::Alphanumeric => [9, 11, 13][$band],
            self::Byte => [8, 16, 16][$band],
        };
    }
}
