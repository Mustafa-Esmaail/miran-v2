<?php

namespace App\Enums;

enum ImpersonationEventType: string
{
    case Started = 'started';
    case Ended = 'ended';
    case RouteVisited = 'route_visited';
}
