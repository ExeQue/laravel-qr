<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use InvalidArgumentException;

final class InvalidLogoException extends InvalidArgumentException implements QrExceptionInterface
{
    public static function notAnSvg(): self
    {
        return new self('The logo is not a readable SVG document.');
    }

    public static function doctype(): self
    {
        return new self('An SVG logo may not have a DOCTYPE or entities.');
    }

    public static function unsupportedType(): self
    {
        return new self('The logo is not a PNG, JPEG, WebP, GIF, HEIC, HEIF, AVIF or SVG image.');
    }

    public static function heifNeedsImageMagick(string $format): self
    {
        return new self("A {$format} logo needs the imagick PHP extension with ImageMagick's {$format} support (libheif), to turn it into a PNG.");
    }

    public static function unreadable(): self
    {
        return new self('The logo image cannot be read.');
    }

    public static function missingFile(string $path): self
    {
        return new self("The logo file \"{$path}\" does not exist or cannot be read.");
    }

    public static function notADataUri(): self
    {
        return new self('The logo is not a data: URI of an image.');
    }


    public static function sizeOutOfRange(float $size): self
    {
        return new self('A logo is above 0 and at most 1 of the symbol wide, '.\ExeQue\Qr\Support\Number::format($size).' given.');
    }

    public static function negativePadding(int $padding): self
    {
        return new self("A logo's padding is at least 0 modules, {$padding} given.");
    }

    public static function coversFunctionPatterns(): self
    {
        return new self('The logo, where it is placed, covers part of a finder, timing, alignment, format or version pattern; place it elsewhere or make it smaller.');
    }

    public static function unreadableSource(string $type): self
    {
        return new self("A logo cannot be read from {$type}: give a Logo, a path, a data: URI, an SVG, image bytes, or a disk and a path.");
    }

    public static function noSource(): self
    {
        return new self('The logo has no image: call from() first.');
    }

}
