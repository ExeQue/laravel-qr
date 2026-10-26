<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use ExeQue\Qr\Support\Number;
use InvalidArgumentException;

final class InvalidColorException extends InvalidArgumentException implements QrExceptionInterface
{
    public static function unparsable(string $value, ?self $reason = null): self
    {
        $message = "\"{$value}\" is not a color".($reason === null ? '.' : ': '.lcfirst($reason->getMessage()));

        return new self($message, previous: $reason);
    }

    public static function outOfRange(string $class, string $component, float $value, float $min, float $max): self
    {
        $name = substr($class, strrpos($class, '\\') + 1);

        return new self(sprintf('%s %s must be %s to %s, %s given.', $name, $component, Number::format($min), Number::format($max), Number::format($value)));
    }

    public static function invalidName(string $name): self
    {
        return new self("\"{$name}\" cannot name a color: use letters, digits and dashes, starting with a letter.");
    }

    public static function builtInName(string $name): self
    {
        return new self("\"{$name}\" is a CSS color name and cannot be redefined.");
    }

    public static function componentCount(string $function, int $expected, int $given): self
    {
        return new self("{$function}() takes {$expected} components, {$given} given.");
    }

    public static function unknownFunction(string $function): self
    {
        return new self("{$function}() is not a supported color function.");
    }

    public static function unnamed(string $hex): self
    {
        return new self("{$hex} has no CSS name.");
    }

    public static function currentColorHasNoValue(): self
    {
        return new self('currentColor has no value of its own; only SVG output can use it.');
    }

    /**
     * @param array<array-key, mixed> $value
     */
    public static function unknownArray(array $value): self
    {
        return new self('A color array needs r, g and b, or a "space" key; got '.json_encode($value).'.');
    }
}
