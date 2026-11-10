<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\LessonType;
use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonSortOrderPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_sort_order_defaults_to_zero_when_omitted(): void
    {
        $course = $this->makeCourse();

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => ['en' => 'Intro', 'ar' => 'Intro'],
            'content' => ['en' => 'Content', 'ar' => 'Content'],
            'type' => LessonType::Text,
            'is_preview' => false,
        ]);

        $this->assertSame(0, $lesson->fresh()->sort_order);
    }

    public function test_lesson_sort_order_accepts_null(): void
    {
        $course = $this->makeCourse();

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => ['en' => 'Intro', 'ar' => 'Intro'],
            'content' => ['en' => 'Content', 'ar' => 'Content'],
            'type' => LessonType::Text,
            'is_preview' => false,
            'sort_order' => null,
        ]);

        $this->assertNull($lesson->fresh()->sort_order);
    }

    private function makeCourse(): Course
    {
        $category = Category::create([
            'name' => ['en' => 'Category', 'ar' => 'Category'],
            'slug' => 'category-'.uniqid(),
            'is_active' => true,
        ]);

        return Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course', 'ar' => 'Course'],
            'summary' => ['en' => 'Summary', 'ar' => 'Summary'],
            'description' => ['en' => 'Description', 'ar' => 'Description'],
            'slug' => 'course-'.uniqid(),
            'status' => CourseStatus::Published,
        ]);
    }
}
