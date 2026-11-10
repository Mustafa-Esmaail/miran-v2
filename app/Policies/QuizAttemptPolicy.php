<?php

namespace App\Policies;

use App\Models\QuizAttempt;
use App\Models\User;

class QuizAttemptPolicy
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
    public function view(User $user, QuizAttempt $quizAttempt): bool
    {
        return $user->can('quizzes.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function delete(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, QuizAttempt $quizAttempt): bool
    {
        return false;
    }
}
