<?php

namespace App\Policies;

use App\Models\ChatRoomParticipant;
use App\Models\User;

class ChatRoomParticipantPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('chat_room_participants.viewAny');
    }

    public function view(User $user, ChatRoomParticipant $chatRoomParticipant): bool
    {
        return $user->can('chat_room_participants.view');
    }

    public function create(User $user): bool
    {
        return $user->can('chat_room_participants.create');
    }

    public function update(User $user, ChatRoomParticipant $chatRoomParticipant): bool
    {
        return $user->can('chat_room_participants.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('chat_room_participants.delete');
    }

    public function delete(User $user, ChatRoomParticipant $chatRoomParticipant): bool
    {
        return $user->can('chat_room_participants.delete');
    }
}
