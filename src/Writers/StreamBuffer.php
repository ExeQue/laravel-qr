<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers;

use ExeQue\Qr\Exceptions\DetachedStreamException;
use Generator;

/**
 * Bytes in a temporary stream that it owns: in memory while small, on disk
 * beyond that. The handle never leaves it, so nothing else can close or move
 * it, until it is detached: then it is handed over for good.
 */
final class StreamBuffer
{
    /**
     * @var resource|null Null once detached.
     */
    private $handle;

    public function __construct(
        private readonly StreamFactory $streams = new StreamFactory(),
    ) {
        $this->handle = $streams->open();
    }

    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    public function write(string $bytes): self
    {
        // Always at the end, wherever a reader left the position.
        $handle = $this->owned();
        fseek($handle, 0, SEEK_END);
        fwrite($handle, $bytes);

        return $this;
    }

    public function contents(): string
    {
        return (string) stream_get_contents($this->owned(), offset: 0);
    }

    /**
     * The bytes in pieces, from the start, without holding them all at once.
     *
     * @return Generator<int, string>
     */
    public function chunks(int $length = 8192): Generator
    {
        $offset = 0;

        // Each chunk is read from where this reader left off, so other reads in between
        // (contents(), another chunks()) cannot move it.
        while (($chunk = (string) stream_get_contents($this->owned(), max(1, $length), $offset)) !== '') {
            $offset += strlen($chunk);

            yield $chunk;
        }
    }

    /**
     * A new temporary stream with a copy of the bytes, at its start; it is the caller's to close.
     *
     * @return resource
     */
    public function copy(): mixed
    {
        $copy = $this->streams->open();
        $this->copyTo($copy);
        rewind($copy);

        return $copy;
    }

    /**
     * Copies the bytes into another stream, which stays the caller's.
     *
     * @param resource $target
     *
     * @return int The bytes copied.
     */
    public function copyTo(mixed $target): int
    {
        $handle = $this->owned();
        rewind($handle);

        return (int) stream_copy_to_stream($handle, $target);
    }

    public function size(): int
    {
        return fstat($this->owned())['size'] ?? 0;
    }

    /**
     * Hands the handle over, at its start: from now on closing it is the caller's job,
     * and this stream cannot be used any more.
     *
     * @return resource
     */
    public function detach(): mixed
    {
        $handle = $this->owned();
        rewind($handle);
        $this->handle = null;

        return $handle;
    }

    public function isDetached(): bool
    {
        return $this->handle === null;
    }

    /**
     * @return resource
     */
    private function owned(): mixed
    {
        return $this->handle ?? throw DetachedStreamException::make();
    }
}
