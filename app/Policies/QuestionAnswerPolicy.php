<?php

namespace App\Policies;

use App\Models\QuestionAnswer;
use App\Models\User;

class QuestionAnswerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('question_answers.viewAny');
    }

    public function view(User $user, QuestionAnswer $questionAnswer): bool
    {
        return $user->can('question_answers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('question_answers.create');
    }

    public function update(User $user, QuestionAnswer $questionAnswer): bool
    {
        return $user->can('question_answers.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('question_answers.delete');
    }

    public function delete(User $user, QuestionAnswer $questionAnswer): bool
    {
        return $user->can('question_answers.delete');
    }
}
