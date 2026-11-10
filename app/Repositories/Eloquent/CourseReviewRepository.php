<?php

namespace App\Repositories\Eloquent;

use App\Models\CourseReview;
use App\Repositories\Contracts\CourseReviewRepositoryInterface;
use Illuminate\Support\Collection;

class CourseReviewRepository implements CourseReviewRepositoryInterface
{
    public function create(array $data): CourseReview
    {
        return CourseReview::query()->create($data);
    }

    public function getApprovedForCourse(int $courseId, int $limit = 6): Collection
    {
        return CourseReview::query()
            ->approved()
            ->where('course_id', $courseId)
            ->with('user')
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getSummaryForCourse(int $courseId): array
    {
        $baseQuery = CourseReview::query()
            ->approved()
            ->where('course_id', $courseId);

        $counts = (clone $baseQuery)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->all();

        $reviewsCount = array_sum($counts);
        $average = $reviewsCount > 0 ? round((float) (clone $baseQuery)->avg('rating'), 1) : null;
        $satisfiedCount = (clone $baseQuery)->where('rating', '>=', 4)->count();
        $satisfactionPercent = $reviewsCount > 0
            ? (int) round(($satisfiedCount / $reviewsCount) * 100)
            : 0;

        $breakdown = [];
        for ($rating = 5; $rating >= 1; $rating--) {
            $count = (int) ($counts[$rating] ?? 0);
            $percent = $reviewsCount > 0 ? (int) round(($count / $reviewsCount) * 100) : 0;

            $breakdown[] = [
                'rating' => $rating,
                'count' => $count,
                'percent' => $percent,
            ];
        }

        return [
            'average' => $average,
            'count' => $reviewsCount,
            'satisfaction_percent' => $satisfactionPercent,
            'breakdown' => $breakdown,
        ];
    }
}
