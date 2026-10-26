<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers;

use ExeQue\Qr\Writers\Concerns\BuffersOutput;
use ExeQue\Qr\Writers\Contracts\Output;

/**
 * Any written code held in a buffer, by its media type: what comes back from the cache.
 */
final readonly class BufferedOutput implements Output
{
    use BuffersOutput;

    public function __construct(
        private StreamBuffer $stream,
        private string $mimeType,
        private string $extension,
    ) {
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function extension(): string
    {
        return $this->extension;
    }
}
