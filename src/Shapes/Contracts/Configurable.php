<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

/**
 * A shape with settings: they are its identity when styles are cached and stored,
 * and it is made again from them as named constructor arguments.
 */
interface Configurable
{
    /**
     * @return array<string, mixed>
     */
    public function options(): array;
}
