<?php

declare(strict_types=1);

namespace ExeQue\Qr\Monocle;

use Closure;
use ExeQue\Monocle\Eyepiece;
use Illuminate\Contracts\Container\Container;

/**
 * Puts the phases of a render on Monocle's timeline, when Monocle is installed and recording;
 * runs them plainly otherwise.
 *
 * @internal
 */
final readonly class Timeline
{
    public function __construct(
        private Container $container,
    ) {
    }

    /**
     * @template T
     *
     * @param Closure(): T $phase
     *
     * @return T
     */
    public function run(string $name, Closure $phase): mixed
    {
        if (! class_exists(Eyepiece::class) || ! $this->container->bound(Eyepiece::class)) {
            return $phase();
        }

        $eyepiece = $this->container->make(Eyepiece::class);

        return $eyepiece->recording() ? $eyepiece->span($name, 'qr')->run($phase) : $phase();
    }
}
