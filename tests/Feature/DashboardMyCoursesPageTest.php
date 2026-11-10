<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMyCoursesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_courses_page_no_longer_shows_the_moodle_username_column(): void
    {
        $student = User::factory()->create();

        $category = Category::create([
            'name' => ['ar' => 'Test Category'],
            'slug' => 'test-cat',
            'is_active' => true,
        ]);

        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Test Course'],
            'summary' => ['ar' => 'Summary'],
            'description' => ['ar' => 'Description'],
            'slug' => 'test-course',
            'status' => 'published',
        ]);

        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Group 1'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        Enrollment::withoutEvents(function () use ($student, $course, $group): void {
            Enrollment::query()->create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'course_group_id' => $group->id,
                'status' => EnrollmentStatus::Active,
                'activated_at' => now(),
            ]);
        });

        $response = $this->actingAs($student, 'web')->get(route('dashboard.courses'));

        $response->assertOk();
        $response->assertSee(__('web.my_courses.enrolment_date'));
        $response->assertSee(__('web.my_courses.course_link'));
        $response->assertDontSee('Moodle User Name');
        $response->assertDontSee('اسم مستخدم مودل');
        $response->assertDontSee('moodle_user_name', false);
    }
}
