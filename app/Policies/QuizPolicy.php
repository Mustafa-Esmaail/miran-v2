<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('quizzes.viewAny');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        return $user->can('quizzes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('quizzes.create');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->can('quizzes.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('quizzes.delete');
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->can('quizzes.delete');
    }
}
