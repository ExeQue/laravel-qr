<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use InvalidArgumentException;

final class InvalidStyleException extends InvalidArgumentException implements QrExceptionInterface
{
    public static function unreadable(string $part, mixed $value): self
    {
        return new self("A stored {$part} cannot be read: ".json_encode($value).'.');
    }

    public static function logoWithoutSource(): self
    {
        return new self('A stored logo has no source to read it from: give it a path or a data: URI as "source".');
    }

    /**
     * @param list<string> $chain
     */
    public static function cycle(array $chain): self
    {
        return new self('The styles extend each other in a circle: '.implode(' extends ', $chain).'.');
    }

    public static function negativeMargin(int $margin): self
    {
        return new self("The margin is at least 0 modules, {$margin} given.");
    }

    public static function noLayers(): self
    {
        return new self('A layered paint needs at least one layer.');
    }

    public static function nestedLayers(): self
    {
        return new self('A layer cannot be a layered paint itself: give its layers instead.');
    }

    public static function opacityOutOfRange(float $opacity): self
    {
        return new self("An opacity is between 0 and 1, {$opacity} given.");
    }
}
