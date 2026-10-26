<?php

declare(strict_types=1);

namespace ExeQue\Qr\Encoder\Enums;

enum ErrorCorrection: string
{
    case Low = 'L';
    case Medium = 'M';
    case Quartile = 'Q';
    case High = 'H';

    /**
     * The share of codewords this level can restore, roughly.
     */
    public function recoveryRatio(): float
    {
        return match ($this) {
            self::Low => 0.07,
            self::Medium => 0.15,
            self::Quartile => 0.25,
            self::High => 0.30,
        };
    }

    /**
     * The level's two bits in the format information (ISO/IEC 18004:2015, table 12).
     *
     * @internal
     */
    public function formatBits(): int
    {
        return match ($this) {
            self::Low => 0b01,
            self::Medium => 0b00,
            self::Quartile => 0b11,
            self::High => 0b10,
        };
    }
}
