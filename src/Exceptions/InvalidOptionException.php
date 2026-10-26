<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use InvalidArgumentException;

final class InvalidOptionException extends InvalidArgumentException implements QrExceptionInterface
{
    /**
     * @param list<string> $accepted
     */
    public static function unknown(string $class, string $option, array $accepted): self
    {
        $takes = $accepted === [] ? 'it takes no options' : 'it takes '.implode(', ', $accepted);

        return new self("{$class} has no option \"{$option}\"; {$takes}.");
    }

    public static function outOfRange(string $class, string $option, float $min, float $max, float $value): self
    {
        return new self("{$class}'s \"{$option}\" must be from {$min} to {$max}, {$value} given.");
    }
}
