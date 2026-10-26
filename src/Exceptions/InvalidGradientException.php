<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use ExeQue\Qr\Support\Number;
use InvalidArgumentException;

final class InvalidGradientException extends InvalidArgumentException implements QrExceptionInterface
{
    public static function positionOutOfRange(float $position): self
    {
        return new self('A color stop sits at 0 to 1, '.Number::format($position).' given.');
    }

    public static function descendingStop(float $position, float $previous): self
    {
        return new self('Color stops go in order: '.Number::format($position).' comes after '.Number::format($previous).'.');
    }

    public static function hintOutsideStops(float $hint, float $from, float $to): self
    {
        return new self('A color hint sits between its stops, '.Number::format($from).' and '.Number::format($to).'; '.Number::format($hint).' given.');
    }

    public static function tooFewStops(int $count): self
    {
        return new self("A gradient needs at least two color stops, {$count} given.");
    }

    public static function focalOutsideRadius(): self
    {
        return new self('A radial gradient\'s focal point lies inside its ellipse.');
    }

    public static function emptyMesh(): self
    {
        return new self('A mesh gradient needs at least two rows of at least two colors, every row as long.');
    }

    public static function notPositive(string $name, float $value): self
    {
        return new self("A gradient's {$name} is above 0, ".Number::format($value).' given.');
    }
}
