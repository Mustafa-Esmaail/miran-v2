<?php

namespace App\Enums;

enum CrmTaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Done = 'done';
}
