<?php

declare(strict_types=1);

namespace ExeQue\Qr\Writers\Contracts;

use ExeQue\Qr\Rendering\Scene;

/**
 * Turns a rendered scene into a file. A writer takes its options when made,
 * so every writer is used the same way.
 */
interface Writer
{
    public function write(Scene $scene): Output;
}
