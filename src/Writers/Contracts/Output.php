<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Contracts;

/**
 * A written code: its bytes, and what kind of file they make.
 */
interface Output
{
    /**
     * The bytes in pieces, without holding them all at once.
     *
     * @return iterable<int, string>
     */
    public function chunks(int $length = 8192): iterable;

    /**
     * A new stream with a copy of the bytes, at its start; it is the caller's to close.
     *
     * @return resource
     */
    public function resource(): mixed;

    /**
     * The bytes as a stream, at its start, handed over for good: closing it is the
     * caller's job, and the output cannot be read again.
     *
     * @return resource
     */
    public function detach(): mixed;

    public function contents(): string;

    /**
     * Such as `image/svg+xml`.
     */
    public function mimeType(): string;

    /**
     * The usual file extension, without a dot: `svg`.
     */
    public function extension(): string;
}
