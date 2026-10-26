<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use InvalidArgumentException;

final class RegistryException extends InvalidArgumentException implements QrExceptionInterface
{
    /**
     * @param list<string> $known
     */
    public static function unknown(string $kind, string $name, array $known): self
    {
        $known = $known === [] ? 'none are registered' : 'registered are: '.implode(', ', $known);

        return new self("No {$kind} is registered as \"{$name}\"; {$known}.");
    }

    public static function notSerializable(string $kind): self
    {
        return new self("The gradient \"{$kind}\" cannot be stored or read back: it does not implement SerializableGradient.");
    }

    public static function notScopable(string $filter): self
    {
        return new self("The {$filter} filter cannot be limited to roles, a region or corners.");
    }

    public static function invalid(string $kind, string $name, string $contract): self
    {
        return new self("The {$kind} \"{$name}\" is neither a {$contract} nor provides one: register a class name, [class, ...options], a closure, an instance, an object that provides one, or an enum class.");
    }
}
