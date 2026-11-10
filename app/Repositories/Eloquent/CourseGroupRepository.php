<?php

namespace App\Repositories\Eloquent;

use App\Enums\CourseGroupStatus;
use App\Models\CourseGroup;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use Illuminate\Support\Collection;

class CourseGroupRepository implements CourseGroupRepositoryInterface
{
    public function findById(int $id): ?CourseGroup
    {
        return CourseGroup::query()->find($id);
    }

    public function availableForEnrollment(int $groupId): bool
    {
        $group = CourseGroup::query()->find($groupId);
        if (! $group) {
            return false;
        }

        if ($group->enrollment_deadline_at && $group->enrollment_deadline_at->isPast()) {
            return false;
        }

        if ($group->capacity) {
            $count = $group->enrollments()->count();
            if ($count >= $group->capacity) {
                return false;
            }
        }

        return $group->status->value === 'open';
    }

    public function countEnrollments(int $groupId): int
    {
        return CourseGroup::query()->whereKey($groupId)->withCount('enrollments')->value('enrollments_count') ?? 0;
    }

    public function getAvailableForCourse(int $courseId): Collection
    {
        return CourseGroup::query()
            ->where('course_id', $courseId)
            ->where('status', CourseGroupStatus::Open)
            ->where(function ($query) {
                $query->whereNull('enrollment_deadline_at')
                    ->orWhere('enrollment_deadline_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('capacity')
                    ->orWhereRaw(
                        '(select count(*) from enrollments where enrollments.course_group_id = course_groups.id) < capacity'
                    );
            })
            ->orderBy('start_at')
            ->get();
    }
}
