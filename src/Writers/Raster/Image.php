<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Raster;

use ExeQue\Qr\Writers\Concerns\BuffersOutput;
use ExeQue\Qr\Writers\Contracts\Output;
use ExeQue\Qr\Writers\Raster\Enums\ImageFormat;
use ExeQue\Qr\Writers\StreamBuffer;

/**
 * A code as a PNG, JPEG or WebP image, held in a temporary stream.
 */
final readonly class Image implements Output
{
    use BuffersOutput;

    public function __construct(
        private StreamBuffer $stream,
        public ImageFormat $format,
    ) {
    }

    public function mimeType(): string
    {
        return $this->format->mimeType();
    }

    public function extension(): string
    {
        return $this->format->extension();
    }
}
