<?php

namespace App\Enums;

enum ChatMessageActionType: string
{
    case Hidden = 'hidden';
    case Unhidden = 'unhidden';
    case RoomArchived = 'room_archived';
    case ParticipantRemoved = 'participant_removed';
}
