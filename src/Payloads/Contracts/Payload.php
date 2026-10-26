<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads\Contracts;

/**
 * Structured content - a Wi-Fi network, a contact, an event - as the text a code holds.
 */
interface Payload
{
    public function toString(): string;
}
