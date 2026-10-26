<?php

declare(strict_types=1);

namespace ExeQue\Qr\Events;

use ExeQue\Qr\Builders\PendingQr;

/**
 * A code is about to be drawn and written.
 */
final readonly class QrRendering
{
    public function __construct(
        public PendingQr $code,
        public string $format,
    ) {
    }
}
