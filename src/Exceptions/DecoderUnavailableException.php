<?php

declare(strict_types=1);

namespace ExeQue\Qr\Exceptions;

use RuntimeException;

final class DecoderUnavailableException extends RuntimeException implements QrExceptionInterface
{
    public static function node(string $command): self
    {
        return new self("The zxing decoder needs Node.js; \"{$command}\" could not be run (qr.reading.zxing.command).");
    }

    public static function failed(string $command, string $reason): self
    {
        return new self("The zxing decoder could not read the image: \"{$command}\" failed ({$reason}).");
    }

    public static function packages(string $directory, string $error): self
    {
        return new self("The zxing decoder needs zxing-wasm (and @resvg/resvg-wasm for SVG) installed in {$directory}: run `npm install zxing-wasm @resvg/resvg-wasm`. ({$error})");
    }
}
