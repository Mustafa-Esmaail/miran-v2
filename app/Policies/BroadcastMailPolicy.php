<?php

namespace App\Policies;

use App\Models\BroadcastMail;
use App\Models\User;

class BroadcastMailPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('send_mail.viewAny');
    }

    public function view(User $user, BroadcastMail $broadcastMail): bool
    {
        return $user->can('send_mail.view');
    }

    public function create(User $user): bool
    {
        return $user->can('send_mail.create');
    }

    public function update(User $user, BroadcastMail $broadcastMail): bool
    {
        return $user->can('send_mail.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('send_mail.delete');
    }

    public function delete(User $user, BroadcastMail $broadcastMail): bool
    {
        return $user->can('send_mail.delete');
    }
}
