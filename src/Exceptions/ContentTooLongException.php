<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use ExeQue\Qr\Encoder\Enums\ErrorCorrection;
use LengthException;

final class ContentTooLongException extends LengthException implements QrExceptionInterface
{
    public static function for(int $bits, int $capacity, ErrorCorrection $errorCorrection): self
    {
        return new self("The content needs {$bits} bits, but version 40 holds {$capacity} at error correction {$errorCorrection->name}.");
    }
}
