<?php

namespace App\Enums;

enum AssignmentSubmissionStatus: string
{
    case Submitted = 'submitted';
    case Graded = 'graded';
}
