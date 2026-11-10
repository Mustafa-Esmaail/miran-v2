<?php

namespace App\Enums;

enum ServiceRequestStatusOutcome: string
{
    case Open = 'open';
    case Won = 'won';
    case Lost = 'lost';
}
