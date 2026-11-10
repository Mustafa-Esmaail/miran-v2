<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notifications.viewAny');
    }

    public function view(User $user, DatabaseNotification $databaseNotification): bool
    {
        return $user->can('notifications.view');
    }

    public function create(User $user): bool
    {
        return $user->can('notifications.create');
    }

    public function update(User $user, DatabaseNotification $databaseNotification): bool
    {
        return $user->can('notifications.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('notifications.delete');
    }

    public function delete(User $user, DatabaseNotification $databaseNotification): bool
    {
        return $user->can('notifications.delete');
    }
}
