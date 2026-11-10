<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentNullableOrderItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrollment_can_be_created_without_order_item_id(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'name' => ['en' => 'Category'],
            'slug' => 'cat-1',
            'is_active' => true,
        ]);

        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-1',
            'status' => 'published',
        ]);

        $group = CourseGroup::create([
            'course_id' => $course->id,
            'title' => ['en' => 'Group'],
            'group_price_amount' => 1000,
            'status' => 'open',
        ]);

        $enrollment = Enrollment::query()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'course_group_id' => $group->id,
            'status' => 'active',
        ]);

        $this->assertNotNull($enrollment->id);
        $this->assertNull($enrollment->order_item_id);
    }
}
