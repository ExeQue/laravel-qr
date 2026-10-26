<?php

declare(strict_types=1);

namespace ExeQue\Qr\Filters\Contracts;

interface ProvidesQrFilter
{
    public function qrFilter(): NamedFilter;
}
