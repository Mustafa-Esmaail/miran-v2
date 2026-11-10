<?php

namespace App\Policies;

use App\Models\QuestionVote;
use App\Models\User;

class QuestionVotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('question_votes.viewAny');
    }

    public function view(User $user, QuestionVote $questionVote): bool
    {
        return $user->can('question_votes.view');
    }

    public function create(User $user): bool
    {
        return $user->can('question_votes.create');
    }

    public function update(User $user, QuestionVote $questionVote): bool
    {
        return $user->can('question_votes.update');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('question_votes.delete');
    }

    public function delete(User $user, QuestionVote $questionVote): bool
    {
        return $user->can('question_votes.delete');
    }
}
