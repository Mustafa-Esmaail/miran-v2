<?php

namespace App\Repositories\Contracts;

use App\Models\CourseQuestion;
use Illuminate\Database\Eloquent\Builder;

interface CourseQuestionRepositoryInterface
{
    public function query(): Builder;

    public function create(array $data): CourseQuestion;

    public function findById(int $id): ?CourseQuestion;
}
