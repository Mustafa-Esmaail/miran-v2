<?php

namespace App\Repositories\Eloquent;

use App\Models\Course;
use App\Models\User;
use App\Repositories\Contracts\CourseRepositoryInterface;
use App\Services\Admin\CourseAccessService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CourseRepository implements CourseRepositoryInterface
{
    public function paginatePublished(int $perPage = 12)
    {
        return Course::query()->published()->with('category')->paginate($perPage);
    }

    public function findPublishedBySlug(string $slug): ?Course
    {
        return Course::query()->published()
            ->where('slug', $slug)
            ->with(['category', 'groups' => function ($query) {
                $query->withCount('enrollments');
            }, 'lessons', 'attachments', 'quizzes', 'trainers', 'discounts', 'sessions'])
            ->withCount('enrollments')
            ->first();
    }

    public function findById(int $id): ?Course
    {
        return Course::query()->find($id);
    }

    public function countAll(): int
    {
        return Course::query()->count();
    }

    public function countForUser(User $user): int
    {
        $query = Course::query();

        return app(CourseAccessService::class)->scopeCourses($query, $user, 'courses.viewAny')->count();
    }

    public function getFeaturedCourses(array $filters, int $limit = 6)
    {
        $cacheKey = 'featured_courses:'.md5(json_encode($filters));

        return Cache::remember($cacheKey, 300, function () use ($filters, $limit) {
            $query = Course::query()
                ->published()
                ->with(['category', 'discounts'])
                ->withCount('enrollments', 'lessons')
                ->withCount(['reviews as reviews_count' => function (Builder $reviewQuery) {
                    $reviewQuery->approved();
                }])
                ->withAvg(['reviews as reviews_avg_rating' => function (Builder $reviewQuery) {
                    $reviewQuery->approved();
                }], 'rating');

            if (! empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            if (! empty($filters['upcoming_only'])) {
                $query->whereHas('groups', function (Builder $groupQuery) {
                    $groupQuery->whereDate('start_at', '>=', Carbon::today());
                });
            }

            if (array_key_exists('min_price', $filters) && $filters['min_price'] !== null && $filters['min_price'] !== '') {
                $query->where('price', '>=', $filters['min_price']);
            }

            if (array_key_exists('max_price', $filters) && $filters['max_price'] !== null && $filters['max_price'] !== '') {
                $query->where('price', '<=', $filters['max_price']);
            }

            return $query->orderByDesc('enrollments_count')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Courses from the same category or duration group; featured courses when there are none.
     */
    public function getRelatedCourses(Course $course, int $limit = 3)
    {
        $cacheKey = 'related_courses:'.$course->id.':'.($course->duration_group ?? 'none').':'.$limit;

        return Cache::remember($cacheKey, 300, function () use ($course, $limit) {
            $related = $this->relatedCoursesQuery($course)
                ->where(function ($query) use ($course) {
                    $query->where('category_id', $course->category_id);
                    if ($course->duration_group) {
                        $query->orWhere('duration_group', $course->duration_group);
                    }
                })
                ->limit($limit)
                ->get();

            if ($related->isNotEmpty()) {
                return $related;
            }

            return $this->relatedCoursesQuery($course)
                ->where('is_featured', true)
                ->limit($limit)
                ->get();
        });
    }

    private function relatedCoursesQuery(Course $course): Builder
    {
        return Course::query()
            ->published()
            ->where('id', '!=', $course->id)
            ->with(['category', 'discounts', 'groups'])
            ->withCount('lessons');
    }
}
