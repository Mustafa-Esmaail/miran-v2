<?php

namespace App\Enums;

enum ServiceRequestPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
}
