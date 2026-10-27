<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster\Enums;

use ExeQue\Qr\Exceptions\InvalidOptionException;
use ExeQue\Qr\Exceptions\MissingExtensionException;

/**
 * What draws PNG, JPEG and WebP codes: Imagick, GD, or whichever is loaded, Imagick first.
 */
enum Driver: string
{
    case Auto = 'auto';
    case Imagick = 'imagick';
    case Gd = 'gd';

    /**
     * The driver asked for, `qr.raster.driver` when none is, with Auto made the one that is loaded.
     */
    public static function resolve(self|string|null $driver = null): self
    {
        $name = $driver instanceof self ? $driver->value : ($driver ?? (string) config('qr.raster.driver', 'auto'));
        $driver = self::tryFrom($name) ?? throw InvalidOptionException::notOneOf(self::class, 'driver', array_column(self::cases(), 'value'), $name);

        return match (true) {
            $driver !== self::Auto => $driver,
            extension_loaded('imagick') => self::Imagick,
            extension_loaded('gd') => self::Gd, // @codeCoverageIgnore
            default => throw MissingExtensionException::raster(), // @codeCoverageIgnore
        };
    }
}
