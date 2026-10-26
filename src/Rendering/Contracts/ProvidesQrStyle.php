<?php

declare(strict_types=1);

namespace ExeQue\Qr\Rendering\Contracts;

use ExeQue\Qr\Rendering\Style;

/**
 * Anything that can hand over a style: an enum of an application's designs,
 * a model with a saved design, a settings object.
 */
interface ProvidesQrStyle
{
    public function qrStyle(): Style;
}
