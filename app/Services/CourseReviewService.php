<?php

namespace App\Services;

use App\Enums\CourseReviewStatus;
use App\Enums\EnrollmentStatus;
use App\Jobs\Nelc\SyncRatedJob;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\User;
use App\Repositories\Contracts\CourseReviewRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CourseReviewService
{
    public function __construct(private readonly CourseReviewRepositoryInterface $reviews) {}

    public function canReview(Course $course, User $user): bool
    {
        return Enrollment::query()
            ->where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->where('status', EnrollmentStatus::Active)
            ->whereNotNull('order_item_id')
            ->exists();
    }

    public function addReview(Course $course, User $user, int $rating, string $content): CourseReview
    {
        if (! $this->canReview($course, $user)) {
            throw new \RuntimeException(__('web.course.review_not_allowed'));
        }

        $rating = max(1, min(5, $rating));

        $review = $this->reviews->create([
            'course_id' => $course->id,
            'user_id' => $user->id,
            'rating' => $rating,
            'content' => $content,
            'status' => CourseReviewStatus::Pending,
        ]);

        SyncRatedJob::dispatch($review->id);

        return $review;
    }

    public function approve(CourseReview $review): CourseReview
    {
        $review->forceFill(['status' => CourseReviewStatus::Approved])->save();

        return $review;
    }

    public function reject(CourseReview $review): CourseReview
    {
        $review->forceFill(['status' => CourseReviewStatus::Rejected])->save();

        return $review;
    }

    public function approveMany(array $ids): void
    {
        DB::table('course_reviews')->whereIn('id', $ids)->update([
            'status' => CourseReviewStatus::Approved->value,
            'updated_at' => now(),
        ]);
    }

    public function rejectMany(array $ids): void
    {
        DB::table('course_reviews')->whereIn('id', $ids)->update([
            'status' => CourseReviewStatus::Rejected->value,
            'updated_at' => now(),
        ]);
    }

    public function getSummary(Course $course): array
    {
        return $this->reviews->getSummaryForCourse($course->id);
    }

    public function getRecentReviews(Course $course, int $limit = 6): Collection
    {
        return $this->reviews->getApprovedForCourse($course->id, $limit);
    }
}
