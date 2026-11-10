<?php

namespace Tests\Unit;

use App\Enums\CourseGroupStatus;
use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\ViewModels\CourseShowViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CourseShowViewModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_defaults_rating_to_five_and_maps_accreditation_number(): void
    {
        $category = Category::create([
            'name' => ['en' => 'Category'],
            'slug' => 'category',
            'is_active' => true,
        ]);

        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course',
            'status' => CourseStatus::Published,
            'course_level' => CourseLevel::Beginner,
            'accreditation_number' => '11199253',
        ]);

        CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group 1'],
            'group_price_amount' => 0,
            'status' => CourseGroupStatus::Open,
        ]);

        $course->load('groups');

        $viewModel = CourseShowViewModel::fromCourse($course, new Collection, [
            'average' => null,
            'count' => 0,
            'breakdown' => [],
            'satisfaction_percent' => 0,
        ]);

        $this->assertSame('11199253', $viewModel->accreditationNumber);
        $this->assertSame(5.0, $viewModel->ratingAverage);
        $this->assertSame(0, $viewModel->reviewsCount);
        $this->assertCount(1, $viewModel->groups);
    }
}
