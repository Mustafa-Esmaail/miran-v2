<?php

namespace App\Repositories\Contracts;

use App\Models\QuestionVote;
use Illuminate\Database\Eloquent\Builder;

interface QuestionVoteRepositoryInterface
{
    public function query(): Builder;

    public function updateOrCreate(array $attributes, array $values): QuestionVote;
}
