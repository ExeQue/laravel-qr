<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Concerns;

use ExeQue\Qr\Writers\StreamBuffer;

/**
 * The reading side of an output held in a `StreamBuffer`.
 *
 * @internal
 *
 * @property-read StreamBuffer $stream
 */
trait BuffersOutput
{
    public function chunks(int $length = 8192): iterable
    {
        return $this->stream->chunks($length);
    }

    public function resource(): mixed
    {
        return $this->stream->copy();
    }

    public function detach(): mixed
    {
        return $this->stream->detach();
    }

    public function contents(): string
    {
        return $this->stream->contents();
    }
}
