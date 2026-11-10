<?php

namespace App\Repositories\Eloquent;

use App\Models\QuestionVote;
use App\Repositories\Contracts\QuestionVoteRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class QuestionVoteRepository implements QuestionVoteRepositoryInterface
{
    public function query(): Builder
    {
        return QuestionVote::query();
    }

    public function updateOrCreate(array $attributes, array $values): QuestionVote
    {
        return QuestionVote::query()->updateOrCreate($attributes, $values);
    }
}
