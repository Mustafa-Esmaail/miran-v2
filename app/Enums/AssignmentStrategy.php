<?php

namespace App\Enums;

enum AssignmentStrategy: string
{
    case RoundRobin = 'round_robin';
    case LeastLoaded = 'least_loaded';
}
