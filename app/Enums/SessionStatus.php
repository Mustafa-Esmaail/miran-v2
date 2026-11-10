<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Upcoming = 'upcoming';
    case Live = 'live';
    case Ended = 'ended';
}
