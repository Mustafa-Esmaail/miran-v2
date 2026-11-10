<?php

namespace App\Policies;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notification_preferences.viewAny');
    }

    public function view(User $user, NotificationPreference $notificationPreference): bool
    {
        return $user->can('notification_preferences.view');
    }

    public function create(User $user): bool
    {
        return $user->can('notification_preferences.create');
    }

    public function update(User $user, NotificationPreference $notificationPreference): bool
    {
        return $user->can('notification_preferences.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('notification_preferences.delete');
    }

    public function delete(User $user, NotificationPreference $notificationPreference): bool
    {
        return $user->can('notification_preferences.delete');
    }
}
