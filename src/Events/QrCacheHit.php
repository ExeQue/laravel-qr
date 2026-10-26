<?php

declare(strict_types=1);

namespace ExeQue\Qr\Events;

use ExeQue\Qr\Builders\PendingQr;

/**
 * A code came from the cache, not drawn again.
 */
final readonly class QrCacheHit
{
    public function __construct(
        public PendingQr $code,
        public string $format,
        public string $key,
    ) {
    }
}
