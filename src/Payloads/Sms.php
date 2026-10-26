<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads;

use ExeQue\Qr\Payloads\Contracts\Payload;

/**
 * A text message to send: `SMSTO:number:message`, as phones' scanners read it.
 */
final readonly class Sms implements Payload
{
    public function __construct(
        public string $number,
        public ?string $message = null,
    ) {
    }

    public function message(string $message): self
    {
        return clone($this, ['message' => $message]);
    }

    public function toString(): string
    {
        return "SMSTO:{$this->number}:".($this->message ?? '');
    }
}
