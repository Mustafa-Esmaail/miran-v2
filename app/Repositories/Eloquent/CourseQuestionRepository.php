<?php

namespace App\Repositories\Eloquent;

use App\Models\CourseQuestion;
use App\Repositories\Contracts\CourseQuestionRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class CourseQuestionRepository implements CourseQuestionRepositoryInterface
{
    public function query(): Builder
    {
        return CourseQuestion::query();
    }

    public function create(array $data): CourseQuestion
    {
        return CourseQuestion::query()->create($data);
    }

    public function findById(int $id): ?CourseQuestion
    {
        return CourseQuestion::query()->find($id);
    }
}
