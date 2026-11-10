<?php

namespace App\Policies;

use App\Models\CourseAttachment;
use App\Models\User;

class CourseAttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('attachments.viewAny');
    }

    public function view(User $user, CourseAttachment $attachment): bool
    {
        return $user->can('attachments.view');
    }

    public function create(User $user): bool
    {
        return $user->can('attachments.create');
    }

    public function update(User $user, CourseAttachment $attachment): bool
    {
        return $user->can('attachments.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('attachments.delete');
    }

    public function delete(User $user, CourseAttachment $attachment): bool
    {
        return $user->can('attachments.delete');
    }
}
