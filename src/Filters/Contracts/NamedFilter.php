<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters\Contracts;

/**
 * A filter's identity: what caching, comparing and storing a style go by.
 */
interface NamedFilter
{
    public function name(): string;

    /**
     * The filter's settings, as plain values.
     *
     * @return array<string, mixed>
     */
    public function options(): array;
}
