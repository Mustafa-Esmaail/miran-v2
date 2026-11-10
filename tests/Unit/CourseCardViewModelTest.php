<?php

namespace Tests\Unit;

use App\Enums\CourseLevel;
use App\Enums\CourseReviewStatus;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use App\ViewModels\CourseCardViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCardViewModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_review_aggregates(): void
    {
        $category = Category::create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Test Course'],
            'summary' => ['en' => 'Summary'],
            'slug' => 'test-course',
            'status' => CourseStatus::Published,
            'course_level' => CourseLevel::Beginner,
        ]);

        $reviewerOne = User::factory()->create();
        $reviewerTwo = User::factory()->create();
        $pendingReviewer = User::factory()->create();

        CourseReview::create([
            'course_id' => $course->id,
            'user_id' => $reviewerOne->id,
            'rating' => 5,
            'content' => 'Great!',
            'status' => CourseReviewStatus::Approved,
        ]);

        CourseReview::create([
            'course_id' => $course->id,
            'user_id' => $reviewerTwo->id,
            'rating' => 4,
            'content' => 'Good.',
            'status' => CourseReviewStatus::Approved,
        ]);

        CourseReview::create([
            'course_id' => $course->id,
            'user_id' => $pendingReviewer->id,
            'rating' => 1,
            'content' => 'Pending review.',
            'status' => CourseReviewStatus::Pending,
        ]);

        $course = Course::query()
            ->whereKey($course->id)
            ->withCount(['reviews as reviews_count' => function ($query) {
                $query->approved();
            }])
            ->withAvg(['reviews as reviews_avg_rating' => function ($query) {
                $query->approved();
            }], 'rating')
            ->firstOrFail();

        $card = CourseCardViewModel::fromCourse($course);

        $this->assertSame(2, $card['reviewsCount']);
        $this->assertSame(4.5, $card['ratingAverage']);
    }

    public function test_it_defaults_rating_to_five_when_there_are_no_reviews(): void
    {
        $category = Category::create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'No Reviews Course'],
            'summary' => ['en' => 'Summary'],
            'slug' => 'no-reviews-course',
            'status' => CourseStatus::Published,
            'course_level' => CourseLevel::Beginner,
        ]);

        $course = Course::query()
            ->whereKey($course->id)
            ->withCount(['reviews as reviews_count' => function ($query) {
                $query->approved();
            }])
            ->withAvg(['reviews as reviews_avg_rating' => function ($query) {
                $query->approved();
            }], 'rating')
            ->firstOrFail();

        $card = CourseCardViewModel::fromCourse($course);

        $this->assertSame(0, $card['reviewsCount']);
        $this->assertSame(5.0, $card['ratingAverage']);
        $this->assertSame('5.0', $card['ratingValue']);
    }
}
