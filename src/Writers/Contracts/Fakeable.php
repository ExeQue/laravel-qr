<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Contracts;

use ExeQue\Qr\Rendering\Scene;

/**
 * A writer that can stand in for itself in tests: under `Qr::fake()` it is asked for
 * `fake()` instead of `write()`, and gives output of the same type made cheaply, such as a
 * ready-made file. A writer without it keeps writing for real under the fake.
 */
interface Fakeable
{
    public function fake(Scene $scene): Output;
}
