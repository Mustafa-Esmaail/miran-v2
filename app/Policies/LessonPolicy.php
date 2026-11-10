<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lessons.viewAny');
    }

    public function view(User $user, Lesson $lesson): bool
    {
        return $user->can('lessons.view');
    }

    public function create(User $user): bool
    {
        return $user->can('lessons.create');
    }

    public function update(User $user, Lesson $lesson): bool
    {
        return $user->can('lessons.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('lessons.delete');
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        return $user->can('lessons.delete');
    }
}
