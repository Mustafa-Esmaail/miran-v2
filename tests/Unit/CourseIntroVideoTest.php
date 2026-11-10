<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseIntroVideoTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_intro_video_url_returns_external_url_as_is(): void
    {
        $category = Category::create([
            'name' => ['en' => 'Category'],
            'slug' => 'category-'.random_int(1000, 9999),
            'is_active' => true,
        ]);

        $course = Course::create([
            'category_id' => $category->id,
            'title' => ['en' => 'Course'],
            'summary' => ['en' => 'Summary'],
            'description' => ['en' => 'Description'],
            'slug' => 'course-'.random_int(1000, 9999),
            'status' => 'published',
            'intro_video_path' => 'https://example.com/intro.mp4',
        ]);

        $course->refresh();

        $this->assertSame('https://example.com/intro.mp4', $course->introVideoUrl);
    }
}
