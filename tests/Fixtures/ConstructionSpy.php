<?php

declare(strict_types=1);

namespace Tests\Fixtures;

/**
 * Counts how often it is built, to show a class was never built from a name.
 */
final class ConstructionSpy
{
    public static int $built = 0;

    public function __construct()
    {
        self::$built++;
    }
}
