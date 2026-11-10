<?php

namespace App\Repositories\Contracts;

use App\Models\CourseGroup;
use Illuminate\Support\Collection;

interface CourseGroupRepositoryInterface
{
    public function findById(int $id): ?CourseGroup;

    public function availableForEnrollment(int $groupId): bool;

    public function countEnrollments(int $groupId): int;

    public function getAvailableForCourse(int $courseId): Collection;
}
