<?php

namespace App\Enums;

enum ServiceRequestActivityType: string
{
    case Created = 'created';
    case StatusChanged = 'status_changed';
    case AssignedChanged = 'assigned_changed';
    case TeamTransferred = 'team_transferred';
    case NoteAdded = 'note_added';
    case Converted = 'converted';
}
