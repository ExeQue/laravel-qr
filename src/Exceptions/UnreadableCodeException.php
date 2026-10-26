<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use RuntimeException;

final class UnreadableCodeException extends RuntimeException implements QrExceptionInterface
{
    public static function noCode(): self
    {
        return new self('No QR code could be found in the image.');
    }

    public static function unreadableImage(): self
    {
        return new self('The image could not be read: give PNG, JPEG, WebP or GIF bytes.');
    }

    public static function unreadableFile(): self
    {
        return new self('The file could not be read: the upload did not arrive whole, or the file is missing or not readable.');
    }

    public static function tooDamaged(): self
    {
        return new self('The QR code is too damaged to read: it has more errors than its error correction can repair.');
    }

    public static function badFormat(): self
    {
        return new self('The QR code\'s format information cannot be read.');
    }

    public static function badContent(string $reason): self
    {
        return new self("The QR code's content cannot be read: {$reason}.");
    }

    /**
     * @param list<string> $reasons
     */
    public static function noDecoderRead(array $reasons): self
    {
        return new self('No decoder could read the QR code: '.implode('; ', $reasons));
    }
}
