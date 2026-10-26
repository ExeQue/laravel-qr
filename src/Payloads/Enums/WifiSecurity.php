<?php

declare(strict_types=1);

namespace ExeQue\Qr\Payloads\Enums;

enum WifiSecurity: string
{
    case Wpa = 'WPA';
    case Wep = 'WEP';
    case None = 'nopass';
}
