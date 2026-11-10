<?php

namespace App\Repositories\Contracts;

use App\Models\QuestionAnswer;
use Illuminate\Database\Eloquent\Builder;

interface QuestionAnswerRepositoryInterface
{
    public function query(): Builder;

    public function create(array $data): QuestionAnswer;

    public function findById(int $id): ?QuestionAnswer;
}
