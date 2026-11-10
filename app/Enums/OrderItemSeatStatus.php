<?php

namespace App\Enums;

enum OrderItemSeatStatus: string
{
    case Unassigned = 'unassigned';
    case Invited = 'invited';
    case Enrolled = 'enrolled';
    case Canceled = 'canceled';
}
