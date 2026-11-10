<?php

namespace App\Repositories\Contracts;

use App\Models\Course;

interface CourseRepositoryInterface
{
    public function paginatePublished(int $perPage = 12);

    public function findPublishedBySlug(string $slug): ?Course;

    public function findById(int $id): ?Course;

    public function countAll(): int;

    public function countForUser(\App\Models\User $user): int;

    public function getFeaturedCourses(array $filters, int $limit = 3);

    public function getRelatedCourses(Course $course, int $limit = 3);
}
