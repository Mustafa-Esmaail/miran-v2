<?php

namespace App\Repositories\Contracts;

use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\OrderItem;

interface EnrollmentRepositoryInterface
{
    public function createForOrderItem(OrderItem $item, int $userId, bool $isInvited = false): Enrollment;

    public function existsForUserCourseGroup(int $userId, int $courseGroupId): bool;

    public function existsForUserCourse(int $userId, int $courseId): bool;

    public function createForCourseGroup(OrderItem $item, CourseGroup $group, int $userId, bool $isInvited = false): Enrollment;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function restoreTrashedForUserCourseGroup(int $userId, int $courseGroupId, array $attributes = []): ?Enrollment;
}
