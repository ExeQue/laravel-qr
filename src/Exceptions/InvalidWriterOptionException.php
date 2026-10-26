<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use ExeQue\Qr\Contracts\Paint;
use InvalidArgumentException;

final class InvalidWriterOptionException extends InvalidArgumentException implements QrExceptionInterface
{
    public static function pixelsWithoutSize(): self
    {
        return new self('Coordinates in pixels need a size in pixels.');
    }

    public static function invalidSymbolId(string $id): self
    {
        return new self("\"{$id}\" is not a usable id: start with a letter, then letters, digits, _, - or .");
    }

    public static function notPositive(string $option, int $value): self
    {
        return new self("The {$option} is at least 1, {$value} given.");
    }

    public static function tooLarge(int $size, int $max): self
    {
        return new self("An image is at most {$max} pixels wide, {$size} given.");
    }

    public static function compressionLevelOutOfRange(int $level): self
    {
        return new self("A PNG compression level is from 0 to 9, {$level} given.");
    }

    public static function qualityOutOfRange(int $quality): self
    {
        return new self("The quality is 1 to 100, {$quality} given.");
    }

    public static function unsupportedPaint(Paint $paint): self
    {
        return new self($paint::class.' is neither a color nor a gradient.');
    }
}
