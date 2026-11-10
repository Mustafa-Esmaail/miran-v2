<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('announcements.viewAny');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->can('announcements.view');
    }

    public function create(User $user): bool
    {
        return $user->can('announcements.create');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->can('announcements.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('announcements.delete');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->can('announcements.delete');
    }
}
