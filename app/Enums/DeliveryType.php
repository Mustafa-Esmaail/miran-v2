<?php

namespace App\Enums;

enum DeliveryType: string
{
    case Offline = 'offline';
    case Online = 'online';
    case Hybrid = 'hybrid';
    case RemoteLive = 'remote_live';
}
