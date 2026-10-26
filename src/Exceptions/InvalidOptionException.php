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
     * What is wrong with it, for a message of one's own: unknown, range, choice or list.
     */
    public ?string $problem = null;

    /**
     * The limits it broke, by name: min and max, the values it takes, or how many numbers a list holds.
     *
     * @var array<string, scalar>
     */
    public array $limits = [];

    /**
     * @param list<string> $accepted
     */
    public static function unknown(string $class, string $option, array $accepted): self
    {
        $takes = $accepted === [] ? 'it takes no options' : 'it takes '.implode(', ', $accepted);

        return self::about($option, 'unknown', [], "{$class} has no option \"{$option}\"; {$takes}.");
    }

    public static function outOfRange(string $class, string $option, float $min, float $max, float $value): self
    {
        return self::about($option, 'range', ['min' => $min, 'max' => $max], "{$class}'s \"{$option}\" must be from {$min} to {$max}, {$value} given.");
    }

    /**
     * @param list<string> $accepted
     */
    public static function notOneOf(string $class, string $option, array $accepted, string $value): self
    {
        $values = implode(', ', $accepted);

        return self::about($option, 'choice', ['values' => $values], "{$class}'s \"{$option}\" must be one of {$values}; \"{$value}\" given.");
    }

    public static function notAList(string $class, string $option, int $count): self
    {
        return self::about($option, 'list', ['count' => $count], "{$class}'s \"{$option}\" must be a number or a list of {$count} numbers.");
    }

    /**
     * @param array<string, scalar> $limits
     */
    private static function about(string $option, string $problem, array $limits, string $message): self
    {
        $exception = new self($message);
        $exception->option = $option;
        $exception->problem = $problem;
        $exception->limits = $limits;

        return $exception;
    }
}
