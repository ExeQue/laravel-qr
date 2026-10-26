<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

interface ProvidesQrOuterEye
{
    public function qrOuterEye(): OuterEyeShape;
}
