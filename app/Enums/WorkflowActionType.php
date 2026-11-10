<?php

namespace App\Enums;

enum WorkflowActionType: string
{
    case CreateTask = 'create_task';
    case UpdateStatus = 'update_status';
    case AssignEmployee = 'assign_employee';
}
