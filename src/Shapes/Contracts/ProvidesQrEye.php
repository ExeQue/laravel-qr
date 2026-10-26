<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

/**
 * A whole eye: outer and inner together.
 */
interface ProvidesQrEye extends ProvidesQrOuterEye, ProvidesQrInnerEye
{
}
