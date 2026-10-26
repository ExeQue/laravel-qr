<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use RuntimeException;

final class StreamException extends RuntimeException implements QrExceptionInterface
{
    public static function cannotOpen(string $path): self
    {
        return new self("The stream {$path} cannot be opened.");
    }
}
