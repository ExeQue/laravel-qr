<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use LogicException;

final class DetachedStreamException extends LogicException implements QrExceptionInterface
{
    public static function make(): self
    {
        return new self('The stream was detached and handed over; it can no longer be used here.');
    }
}
