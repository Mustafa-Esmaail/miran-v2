<?php

namespace App\Policies;

use App\Models\ChatMessageAction;
use App\Models\User;

class ChatMessageActionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('chat_message_actions.viewAny');
    }

    public function view(User $user, ChatMessageAction $chatMessageAction): bool
    {
        return $user->can('chat_message_actions.view');
    }

    public function create(User $user): bool
    {
        return $user->can('chat_message_actions.create');
    }

    public function update(User $user, ChatMessageAction $chatMessageAction): bool
    {
        return $user->can('chat_message_actions.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('chat_message_actions.delete');
    }

    public function delete(User $user, ChatMessageAction $chatMessageAction): bool
    {
        return $user->can('chat_message_actions.delete');
    }
}
