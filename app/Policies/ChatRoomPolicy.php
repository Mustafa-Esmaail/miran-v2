<?php

namespace App\Policies;

use App\Models\ChatRoom;
use App\Models\User;

class ChatRoomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('chat_rooms.viewAny');
    }

    public function view(User $user, ChatRoom $chatRoom): bool
    {
        if (! $user->can('chat_rooms.view')) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'super-admin'])) {
            return true;
        }

        return $chatRoom->participants()
            ->where('user_id', $user->id)
            ->whereNull('left_at')
            ->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ChatRoom $chatRoom): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function delete(User $user, ChatRoom $chatRoom): bool
    {
        return false;
    }

    public function forceDelete(User $user, ChatRoom $chatRoom): bool
    {
        return false;
    }
}
