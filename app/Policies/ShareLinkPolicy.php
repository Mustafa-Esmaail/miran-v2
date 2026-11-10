<?php

namespace App\Policies;

use App\Models\ShareLink;
use App\Models\User;

class ShareLinkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('share_links.viewAny');
    }

    public function view(User $user, ShareLink $shareLink): bool
    {
        return $user->can('share_links.view');
    }

    public function create(User $user): bool
    {
        return $user->can('share_links.create');
    }

    public function update(User $user, ShareLink $shareLink): bool
    {
        return $user->can('share_links.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('share_links.delete');
    }

    public function delete(User $user, ShareLink $shareLink): bool
    {
        return $user->can('share_links.delete');
    }
}
