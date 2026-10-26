<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers;

use ExeQue\Qr\Exceptions\StreamException;

/**
 * Opens the temporary streams that written codes are held in.
 */
final readonly class StreamFactory
{
    /**
     * @param int|null $maxMemory Bytes kept in memory before a stream moves to a temporary
     *                            file; null for PHP's own limit (2 MB).
     */
    public function __construct(
        private ?int $maxMemory = null,
    ) {
    }

    /**
     * An empty buffer in a stream of this factory's.
     */
    public function buffer(): StreamBuffer
    {
        return new StreamBuffer($this);
    }

    /**
     * @return resource
     */
    public function open(): mixed
    {
        $path = $this->maxMemory === null ? 'php://temp' : "php://temp/maxmemory:{$this->maxMemory}";

        return fopen($path, 'w+b') ?: throw StreamException::cannotOpen($path);
    }
}
