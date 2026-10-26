<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use InvalidArgumentException;

final class InvalidOptionException extends InvalidArgumentException implements QrExceptionInterface
{
    /**
     * The option at fault, when it is one.
     */
    public ?string $option = null;

    /**
     * @param list<string> $accepted
     */
    public static function unknown(string $class, string $option, array $accepted): self
    {
        $takes = $accepted === [] ? 'it takes no options' : 'it takes '.implode(', ', $accepted);

        return self::about($option, "{$class} has no option \"{$option}\"; {$takes}.");
    }

    public static function outOfRange(string $class, string $option, float $min, float $max, float $value): self
    {
        return self::about($option, "{$class}'s \"{$option}\" must be from {$min} to {$max}, {$value} given.");
    }

    /**
     * @param list<string> $accepted
     */
    public static function notOneOf(string $class, string $option, array $accepted, string $value): self
    {
        return self::about($option, "{$class}'s \"{$option}\" is one of ".implode(', ', $accepted).", {$value} given.");
    }

    private static function about(string $option, string $message): self
    {
        $exception = new self($message);
        $exception->option = $option;

        return $exception;
    }
}
