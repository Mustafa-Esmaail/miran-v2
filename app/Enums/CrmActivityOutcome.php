<?php

namespace App\Enums;

enum CrmActivityOutcome: string
{
    case Reached = 'reached';
    case NoAnswer = 'no_answer';
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case Callback = 'callback';
}
