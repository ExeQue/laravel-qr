<?php

declare(strict_types=1);

namespace ExeQue\Qr\Events;

use ExeQue\Qr\Builders\PendingQr;

/**
 * A code was not in the cache and is being drawn.
 */
final readonly class QrCacheMissed
{
    public function __construct(
        public PendingQr $code,
        public string $format,
        public string $key,
    ) {
    }
}
