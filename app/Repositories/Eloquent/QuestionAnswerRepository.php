<?php

namespace App\Repositories\Eloquent;

use App\Models\QuestionAnswer;
use App\Repositories\Contracts\QuestionAnswerRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class QuestionAnswerRepository implements QuestionAnswerRepositoryInterface
{
    public function query(): Builder
    {
        return QuestionAnswer::query();
    }

    public function create(array $data): QuestionAnswer
    {
        return QuestionAnswer::query()->create($data);
    }

    public function findById(int $id): ?QuestionAnswer
    {
        return QuestionAnswer::query()->find($id);
    }
}
