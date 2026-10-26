<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters\Contracts;

use ExeQue\Qr\Rendering\EyeContext;
use ExeQue\Qr\Rendering\EyeStyle;

/**
 * Changes how the eyes are drawn, one corner at a time, as `ModuleFilter` does for modules.
 */
interface EyeFilter
{
    public function eye(EyeContext $eye, EyeStyle $style): EyeStyle;
}
