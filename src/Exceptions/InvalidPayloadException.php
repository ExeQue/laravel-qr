<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use ExeQue\Qr\Support\Number;
use InvalidArgumentException;

final class InvalidPayloadException extends InvalidArgumentException implements QrExceptionInterface
{
    public static function url(string $url): self
    {
        return new self("\"{$url}\" is not a URL.");
    }

    public static function missing(string $payload, string $field): self
    {
        return new self("A {$payload} needs a {$field}.");
    }

    public static function coordinates(float $latitude, float $longitude): self
    {
        return new self('A latitude is -90 to 90 and a longitude -180 to 180; '.Number::format($latitude, 7).', '.Number::format($longitude, 7).' given.');
    }

    public static function invalid(string $kind, string $field, string $value): self
    {
        return new self("The {$kind} {$field} \"{$value}\" is not valid.");
    }
}
