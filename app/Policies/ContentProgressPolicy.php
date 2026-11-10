<?php

namespace App\Policies;

use App\Models\ContentProgress;
use App\Models\User;

class ContentProgressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('content_progress.viewAny');
    }

    public function view(User $user, ContentProgress $contentProgress): bool
    {
        return $user->can('content_progress.view');
    }

    public function create(User $user): bool
    {
        return $user->can('content_progress.create');
    }

    public function update(User $user, ContentProgress $contentProgress): bool
    {
        return $user->can('content_progress.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('content_progress.delete');
    }

    public function delete(User $user, ContentProgress $contentProgress): bool
    {
        return $user->can('content_progress.delete');
    }
}
