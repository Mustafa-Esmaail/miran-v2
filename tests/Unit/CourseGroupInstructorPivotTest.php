<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseGroupInstructorPivotTest extends TestCase
{
    use RefreshDatabase;

    public function test_syncing_group_instructors_fills_course_id_automatically(): void
    {
        $course = $this->createCourse();
        $group = CourseGroup::query()->create([
            'course_id' => $course->id,
            'title' => ['ar' => 'Group'],
            'group_price_amount' => 10000,
            'status' => 'open',
        ]);
        $instructor = User::factory()->create();

        $group->instructors()->sync([$instructor->id]);

        $this->assertDatabaseHas('course_group_instructors', [
            'course_group_id' => $group->id,
            'user_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
    }

    private function createCourse(): Course
    {
        $category = Category::query()->create([
            'name' => ['ar' => 'Category'],
            'slug' => 'category-'.Str::uuid(),
            'is_active' => true,
        ]);

        return Course::query()->create([
            'category_id' => $category->id,
            'title' => ['ar' => 'Course'],
            'summary' => ['ar' => 'Course'],
            'description' => ['ar' => 'Description'],
            'slug' => 'course-'.Str::uuid(),
            'status' => 'draft',
        ]);
    }
}
