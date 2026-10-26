<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use InvalidArgumentException;

final class InvalidVersionException extends InvalidArgumentException implements QrExceptionInterface
{
    public static function outOfRange(int $version): self
    {
        return new self("A QR code version is 1 to 40, {$version} given.");
    }
}
