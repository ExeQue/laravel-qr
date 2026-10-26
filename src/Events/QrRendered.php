<?php

declare(strict_types=1);

namespace ExeQue\Qr\Events;

use ExeQue\Qr\Builders\PendingQr;
use ExeQue\Qr\Builders\Rendered;
use ExeQue\Qr\Encoder\Enums\ErrorCorrection;

/**
 * A code was drawn and written, or taken from the cache.
 */
final readonly class QrRendered
{
    /**
     * @param array{encode?: float, render?: float, write?: float, total: float} $milliseconds Per phase; only the total for a cached code.
     * @param int|null $version The symbol's version; null when the code came from the cache.
     * @param array<array-key, mixed> $options The writer's options: size, quality and the like.
     */
    public function __construct(
        public PendingQr $code,
        public string $format,
        public Rendered $rendered,
        public array $milliseconds,
        public bool $cached,
        public ?int $version = null,
        public ?ErrorCorrection $errorCorrection = null,
        public ?int $modules = null,
        public array $options = [],
    ) {
    }
}
