<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use ExeQue\Qr\Support\Number;
use RuntimeException;

final class ScanSafetyException extends RuntimeException implements QrExceptionInterface
{
    public static function logoTooLarge(int $cleared, int $budget): self
    {
        return new self("The code is unlikely to scan: its logo clears {$cleared} data cells, more than the {$budget} its error correction can spare. Make the logo smaller or raise the error correction.");
    }

    public static function inverted(): self
    {
        return new self('The code may not scan everywhere: it is lighter than its background, which not every reader scans. Make the code darker than its background, or set scan_safety.inverted to warn.');
    }

    public static function lowContrast(float $ratio, float $min): self
    {
        return new self('The code is unlikely to scan: its colors have only '.Number::format($ratio, 2).':1 contrast against the background, below '.Number::format($min, 2).':1.');
    }
}
