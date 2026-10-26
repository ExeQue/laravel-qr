<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use RuntimeException;

final class MissingExtensionException extends RuntimeException implements QrExceptionInterface
{
    public static function imagick(): self
    {
        return new self('Writing PNG, JPEG or WebP with the imagick driver needs the imagick PHP extension, which is not loaded.');
    }

    public static function gd(): self
    {
        return new self('Writing PNG, JPEG or WebP with the gd driver needs the gd PHP extension, which is not loaded.');
    }

    public static function raster(): self
    {
        return new self('Writing PNG, JPEG or WebP needs the imagick or the gd PHP extension, and neither is loaded.');
    }

    public static function svgLogosWithGd(): self
    {
        return new self('GD cannot draw an SVG logo on a PNG, JPEG or WebP code: use a raster logo, the imagick driver with SVG support, or write SVG.');
    }

    public static function svgLogos(): self
    {
        return new self('An SVG logo on a PNG, JPEG or WebP code needs ImageMagick built with SVG support (librsvg); use a raster logo, or write SVG.');
    }
}
