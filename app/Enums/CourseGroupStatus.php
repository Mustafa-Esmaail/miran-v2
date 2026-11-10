<?php

namespace App\Enums;

enum CourseGroupStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Archived = 'archived';
}
