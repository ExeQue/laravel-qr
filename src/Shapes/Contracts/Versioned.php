<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

/**
 * A shape whose drawing has changed over time: raising the version makes cached
 * codes that use it stale, and only those.
 */
interface Versioned
{
    public function version(): int;
}
