<?php

namespace App\Services;

use App\Enums\EnrollmentStatus;
use App\Models\Bundle;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Repositories\Contracts\EnrollmentRepositoryInterface;

class EnrollmentService
{
    public function __construct(
        private readonly EnrollmentRepositoryInterface $enrollments,
        private readonly CourseGroupRepositoryInterface $groups,
        private readonly NotificationService $notifications
    ) {}

    public function activateForOrder(Order $order): void
    {
        $order->loadMissing('items.purchasable');
        foreach ($order->items as $item) {
            $this->activateForItem($item, $order->user_id, false);
        }
    }

    public function activateForItem(OrderItem $item, int $userId, bool $isInvited = false): void
    {
        $item->loadMissing('purchasable');

        if ($item->purchasable instanceof CourseGroup) {
            if ($this->enrollments->existsForUserCourseGroup($userId, $item->purchasable_id)) {
                return;
            }

            $enrollment = $this->enrollments->createForOrderItem($item, $userId, $isInvited);
            $this->notifications->notifyEnrollmentConfirmed($enrollment);

            return;
        }

        if ($item->purchasable instanceof Bundle) {
            $this->activateBundleEnrollments($item, $item->purchasable, $userId, $isInvited);
        }
    }

    private function activateBundleEnrollments(OrderItem $item, Bundle $bundle, int $userId, bool $isInvited): void
    {
        $bundle->loadMissing('courses');
        $selectedCourseGroups = collect(data_get($item->metadata_snapshot, 'selected_course_groups', []))
            ->mapWithKeys(fn ($groupId, $courseId): array => [(int) $courseId => (int) $groupId])
            ->filter(fn (int $groupId, int $courseId): bool => $courseId > 0 && $groupId > 0);

        foreach ($bundle->courses as $course) {
            $availableGroup = null;
            $selectedGroupId = (int) ($selectedCourseGroups->get((int) $course->id) ?? 0);
            if ($selectedGroupId > 0) {
                $selectedGroup = $this->groups->findById($selectedGroupId);
                if (
                    $selectedGroup &&
                    (int) $selectedGroup->course_id === (int) $course->id &&
                    ! $this->enrollments->existsForUserCourseGroup($userId, $selectedGroup->id)
                ) {
                    $availableGroup = $selectedGroup;
                }
            }

            if (! $availableGroup) {
                $availableGroup = $this->groups
                    ->getAvailableForCourse($course->id)
                    ->first(function (CourseGroup $group) use ($userId) {
                        return ! $this->enrollments->existsForUserCourseGroup($userId, $group->id);
                    });
            }

            if (! $availableGroup) {
                continue;
            }

            $enrollment = $this->enrollments->createForCourseGroup($item, $availableGroup, $userId, $isInvited);
            $this->notifications->notifyEnrollmentConfirmed($enrollment);
        }
    }

    public function syncForItemChange(OrderItem $item, int $orderUserId): void
    {
        $item->loadMissing('purchasable');

        $desiredPairs = $this->desiredCourseGroupPairs($item);

        $unmatchedExisting = Enrollment::query()->where('order_item_id', $item->id)->get();

        foreach ($desiredPairs as [$courseId, $groupId]) {
            $match = $unmatchedExisting->first(fn (Enrollment $enrollment): bool => (int) $enrollment->course_group_id === $groupId)
                ?? $unmatchedExisting->first();

            if ($match) {
                $unmatchedExisting = $unmatchedExisting->reject(fn (Enrollment $enrollment): bool => $enrollment->is($match));

                if ((int) $match->course_group_id !== $groupId && $this->enrollments->existsForUserCourseGroup($orderUserId, $groupId)) {
                    continue;
                }

                $match->update([
                    'user_id' => $orderUserId,
                    'course_id' => $courseId,
                    'course_group_id' => $groupId,
                    'status' => EnrollmentStatus::Active,
                    'activated_at' => now(),
                ]);

                continue;
            }

            if ($this->enrollments->existsForUserCourseGroup($orderUserId, $groupId)) {
                continue;
            }

            $group = $this->groups->findById($groupId);

            if ($group) {
                $this->enrollments->createForCourseGroup($item, $group, $orderUserId, false);
            }
        }

        foreach ($unmatchedExisting as $leftover) {
            $leftover->update(['status' => EnrollmentStatus::Cancelled]);
        }
    }

    /**
     * @return array<int, array{0: int, 1: int}>
     */
    private function desiredCourseGroupPairs(OrderItem $item): array
    {
        if ($item->purchasable instanceof CourseGroup) {
            return [[$item->purchasable->course_id, $item->purchasable->id]];
        }

        if ($item->purchasable instanceof Bundle) {
            return collect(data_get($item->metadata_snapshot, 'selected_course_groups', []))
                ->map(fn ($groupId, $courseId): array => [(int) $courseId, (int) $groupId])
                ->filter(fn (array $pair): bool => $pair[0] > 0 && $pair[1] > 0)
                ->values()
                ->all();
        }

        return [];
    }
}
