<?php

namespace App\Policies;

use App\Models\LessonProgress;
use App\Models\User;

class LessonProgressPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('lesson_progress.viewAny');
    }

    public function view(User $user, LessonProgress $lessonProgress): bool
    {
        return $user->can('lesson_progress.view');
    }

    public function create(User $user): bool
    {
        return $user->can('lesson_progress.create');
    }

    public function update(User $user, LessonProgress $lessonProgress): bool
    {
        return $user->can('lesson_progress.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('lesson_progress.delete');
    }

    public function delete(User $user, LessonProgress $lessonProgress): bool
    {
        return $user->can('lesson_progress.delete');
    }
}
