<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseShowQuestionsTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_show_displays_questions_tab(): void
    {
        $category = Category::query()->create([
            'name' => ['en' => 'Category'],
            'slug' => Str::slug(Str::random(12)),
            'is_active' => true,
        ]);

        $course = Course::query()->create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'slug' => Str::slug(Str::random(12)),
            'status' => CourseStatus::Published,
        ]);

        $this->get(route('courses.show', $course->slug))
            ->assertOk()
            ->assertSee(__('web.questions.tab'));
    }
}
