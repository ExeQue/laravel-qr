<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use InvalidArgumentException;

final class ResolveException extends InvalidArgumentException implements QrExceptionInterface
{
    public static function cannot(mixed $value, string $expected): self
    {
        $given = is_string($value) ? "\"{$value}\"" : get_debug_type($value);

        return new self("{$given} is not {$expected}: give an instance, an object that provides one (such as an enum case), a class name of either, or a registered name.");
    }
}
