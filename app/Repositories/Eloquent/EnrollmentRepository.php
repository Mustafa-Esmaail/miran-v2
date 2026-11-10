<?php

namespace App\Repositories\Eloquent;

use App\Enums\CertificateRequestStatus;
use App\Enums\EnrollmentStatus;
use App\Models\CertificateRequest;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\OrderItem;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

class EnrollmentRepository implements EnrollmentRepositoryInterface
{
    public function createForOrderItem(OrderItem $item, int $userId, bool $isInvited = false): Enrollment
    {
        if (! $item->purchasable instanceof \App\Models\CourseGroup) {
            throw new InvalidArgumentException('Order item is not a course group.');
        }

        $this->clearStaleRejectedCertificateRequest($userId, $item->purchasable->id);

        $attributes = [
            'course_id' => $item->purchasable->course_id,
            'order_item_id' => $item->id,
            'is_invited' => $isInvited,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
        ];

        $restored = $this->restoreTrashedForUserCourseGroup($userId, $item->purchasable->id, $attributes);

        if ($restored) {
            return $restored;
        }

        try {
            return Enrollment::query()->create([
                'user_id' => $userId,
                'course_group_id' => $item->purchasable->id,
                ...$attributes,
            ]);
        } catch (QueryException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }

            return Enrollment::query()
                ->where('user_id', $userId)
                ->where('course_group_id', $item->purchasable->id)
                ->firstOrFail();
        }
    }

    public function existsForUserCourseGroup(int $userId, int $courseGroupId): bool
    {
        return Enrollment::query()->where('user_id', $userId)->where('course_group_id', $courseGroupId)->exists();
    }

    public function existsForUserCourse(int $userId, int $courseId): bool
    {
        return Enrollment::query()->where('user_id', $userId)->where('course_id', $courseId)->exists();
    }

    public function createForCourseGroup(OrderItem $item, CourseGroup $group, int $userId, bool $isInvited = false): Enrollment
    {
        $this->clearStaleRejectedCertificateRequest($userId, $group->id);

        $attributes = [
            'course_id' => $group->course_id,
            'order_item_id' => $item->id,
            'is_invited' => $isInvited,
            'status' => EnrollmentStatus::Active,
            'activated_at' => now(),
        ];

        $restored = $this->restoreTrashedForUserCourseGroup($userId, $group->id, $attributes);

        if ($restored) {
            return $restored;
        }

        try {
            return Enrollment::query()->create([
                'user_id' => $userId,
                'course_group_id' => $group->id,
                ...$attributes,
            ]);
        } catch (QueryException $e) {
            if ((string) $e->getCode() !== '23000') {
                throw $e;
            }

            return Enrollment::query()
                ->where('user_id', $userId)
                ->where('course_group_id', $group->id)
                ->firstOrFail();
        }
    }

    /**
     * The (user_id, course_group_id) unique index also covers soft-deleted rows, so a fresh
     * insert after a delete would collide. Revive the trashed row with the new attributes instead.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function restoreTrashedForUserCourseGroup(int $userId, int $courseGroupId, array $attributes = []): ?Enrollment
    {
        $enrollment = Enrollment::onlyTrashed()
            ->where('user_id', $userId)
            ->where('course_group_id', $courseGroupId)
            ->first();

        if (! $enrollment) {
            return null;
        }

        $enrollment->fill($attributes);
        $enrollment->restore();

        return $enrollment;
    }

    /**
     * A rejected certificate request is tied only to (user, course_group), not to a specific
     * enrollment. If the prior enrollment for this group was removed (e.g. admin cleanup/refund)
     * and the student is now enrolling fresh, drop the stale rejection so they see the normal
     * "submit your data" prompt instead of an old rejection that no longer applies.
     */
    private function clearStaleRejectedCertificateRequest(int $userId, int $courseGroupId): void
    {
        CertificateRequest::query()
            ->where('user_id', $userId)
            ->where('course_group_id', $courseGroupId)
            ->where('status', CertificateRequestStatus::Rejected)
            ->delete();
    }
}
