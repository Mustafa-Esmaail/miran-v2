<?php

namespace App\Enums;

enum QuestionAnswerStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
