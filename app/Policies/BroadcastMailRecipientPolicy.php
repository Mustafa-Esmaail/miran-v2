<?php

namespace App\Policies;

use App\Models\BroadcastMailRecipient;
use App\Models\User;

class BroadcastMailRecipientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('broadcast_mail_recipients.viewAny');
    }

    public function view(User $user, BroadcastMailRecipient $broadcastMailRecipient): bool
    {
        return $user->can('broadcast_mail_recipients.view');
    }

    public function create(User $user): bool
    {
        return $user->can('broadcast_mail_recipients.create');
    }

    public function update(User $user, BroadcastMailRecipient $broadcastMailRecipient): bool
    {
        return $user->can('broadcast_mail_recipients.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('broadcast_mail_recipients.delete');
    }

    public function delete(User $user, BroadcastMailRecipient $broadcastMailRecipient): bool
    {
        return $user->can('broadcast_mail_recipients.delete');
    }
}
