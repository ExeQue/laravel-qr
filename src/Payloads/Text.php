<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads;

use ExeQue\Qr\Payloads\Contracts\Payload;

/**
 * Plain text, as it is.
 */
final readonly class Text implements Payload
{
    public function __construct(
        public string $text,
    ) {
    }

    public function toString(): string
    {
        return $this->text;
    }
}
