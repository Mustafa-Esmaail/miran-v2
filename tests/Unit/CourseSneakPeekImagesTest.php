<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSneakPeekImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_casts_sneak_peek_images_to_array(): void
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
            'sneak_peek_images' => [
                'https://example.com/one.jpg',
                'https://example.com/two.jpg',
            ],
        ]);

        $course->refresh();

        $this->assertIsArray($course->sneak_peek_images);
        $this->assertCount(2, $course->sneak_peek_images);
        $this->assertSame('https://example.com/one.jpg', $course->sneak_peek_images[0]);
    }
}
