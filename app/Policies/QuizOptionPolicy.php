<?php

namespace App\Policies;

use App\Models\QuizOption;
use App\Models\User;

class QuizOptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('quizzes.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, QuizOption $quizOption): bool
    {
        return $user->can('quizzes.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('quizzes.update');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, QuizOption $quizOption): bool
    {
        return $user->can('quizzes.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('quizzes.delete');
    }

    public function delete(User $user, QuizOption $quizOption): bool
    {
        return $user->can('quizzes.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, QuizOption $quizOption): bool
    {
        return $user->can('quizzes.delete');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, QuizOption $quizOption): bool
    {
        return $user->can('quizzes.delete');
    }
}
