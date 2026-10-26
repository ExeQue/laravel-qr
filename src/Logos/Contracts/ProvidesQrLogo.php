<?php

declare(strict_types=1);

namespace ExeQue\Qr\Logos\Contracts;

use ExeQue\Qr\Logos\Logo;

/**
 * Anything that can hand over a logo, such as a model with an uploaded one.
 */
interface ProvidesQrLogo
{
    public function qrLogo(): Logo;
}
