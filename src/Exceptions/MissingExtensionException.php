<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use RuntimeException;

final class MissingExtensionException extends RuntimeException implements QrExceptionInterface
{
    public static function imagick(): self
    {
        return new self('Writing PNG, JPEG or WebP needs the imagick PHP extension, which is not loaded.');
    }

    public static function svgLogos(): self
    {
        return new self('An SVG logo on a PNG, JPEG or WebP code needs ImageMagick built with SVG support (librsvg); use a raster logo, or write SVG.');
    }
}
