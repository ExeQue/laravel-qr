<?php

declare(strict_types=1);

namespace ExeQue\Qr\Events;

use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Rendering\GuardAdjustment;

/**
 * A scan safety guard changed a code, or warns about it.
 */
final readonly class QrGuardApplied
{
    public function __construct(
        public PendingQr $code,
        public GuardAdjustment $adjustment,
    ) {
    }
}
