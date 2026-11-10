<?php

namespace Tests\Feature;

use Tests\TestCase;

class RichContentViewsTest extends TestCase
{
    public function test_rich_content_class_is_used_on_rich_html_views(): void
    {
        $files = [
            'resources/views/livewire/dashboard-course-player.blade.php',
            'resources/views/livewire/courses/course-show.blade.php',
            'resources/views/livewire/blog/blog-detail.blade.php',
            'resources/views/pages/show.blade.php',
            'resources/views/sections/about.blade.php',
            'resources/views/sections/content.blade.php',
            'resources/views/sections/image_text.blade.php',
            'resources/views/sections/policy_text.blade.php',
            'resources/views/home.blade.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents(base_path($file));

            $this->assertNotFalse($contents);
            $this->assertStringContainsString('rich-content', $contents, $file);
        }
    }

    public function test_rich_content_styles_exist_in_app_css(): void
    {
        $contents = file_get_contents(base_path('resources/css/app.css'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('.rich-content {', $contents);
        $this->assertStringContainsString('.rich-content h1 {', $contents);
        $this->assertStringContainsString('.rich-content ul {', $contents);
        $this->assertStringContainsString('.rich-content img {', $contents);
    }
}
