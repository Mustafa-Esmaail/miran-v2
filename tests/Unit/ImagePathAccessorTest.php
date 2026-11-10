<?php

namespace Tests\Unit;

use App\Models\Blog;
use App\Models\Bundle;
use App\Models\Course;
use Tests\TestCase;

class ImagePathAccessorTest extends TestCase
{
    public function test_models_use_image_path_for_accessors(): void
    {
        $course = new Course(['image_path' => 'courses/course.jpg']);
        $bundle = new Bundle(['image_path' => 'bundles/bundle.jpg']);
        $blog = new Blog(['image_path' => 'https://example.com/blog.jpg']);

        $this->assertSame('courses/course.jpg', $course->image_path);
        $this->assertSame('bundles/bundle.jpg', $bundle->image_path);
        $this->assertSame('https://example.com/blog.jpg', $blog->image_path);
        $this->assertSame('https://example.com/blog.jpg', $blog->image_url);
    }
}
