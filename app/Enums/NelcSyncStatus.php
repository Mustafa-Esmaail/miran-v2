<?php

namespace App\Enums;

enum NelcSyncStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
}
