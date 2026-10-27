<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use InvalidArgumentException;

final class InvalidRuleException extends InvalidArgumentException implements QrExceptionInterface
{
    /**
     * @param list<string> $fields
     */
    public static function unknownField(string $field, array $fields): self
    {
        return new self("A style has no field \"{$field}\" to allow; its fields are ".implode(', ', $fields).'.');
    }
}
