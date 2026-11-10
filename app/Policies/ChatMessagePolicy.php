<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('chat_messages.viewAny');
    }

    public function view(User $user, ChatMessage $chatMessage): bool
    {
        return $user->can('chat_messages.view');
    }

    public function create(User $user): bool
    {
        return $user->can('chat_messages.create');
    }

    public function update(User $user, ChatMessage $chatMessage): bool
    {
        return $user->can('chat_messages.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('chat_messages.delete');
    }

    public function delete(User $user, ChatMessage $chatMessage): bool
    {
        return $user->can('chat_messages.delete');
    }
}
