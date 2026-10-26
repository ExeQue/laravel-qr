<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads\Contracts;

/**
 * Anything that can hand over what a code holds, such as a model with its own URL.
 */
interface ProvidesQrContent
{
    public function qrContent(): string|Payload;
}
