<?php

declare(strict_types=1);

namespace ExeQue\Qr\Shapes\Contracts;

interface ProvidesQrInnerEye
{
    public function qrInnerEye(): InnerEyeShape;
}
