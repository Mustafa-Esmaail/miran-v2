<?php

namespace App\Repositories\Contracts;

use App\Models\CourseReview;
use Illuminate\Support\Collection;

interface CourseReviewRepositoryInterface
{
    public function create(array $data): CourseReview;

    public function getApprovedForCourse(int $courseId, int $limit = 6): Collection;

    public function getSummaryForCourse(int $courseId): array;
}
