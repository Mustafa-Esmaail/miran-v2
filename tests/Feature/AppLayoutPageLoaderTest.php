<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppLayoutPageLoaderTest extends TestCase
{
    public function test_app_layout_has_page_loader_disabled_comment(): void
    {
        $contents = file_get_contents(base_path('resources/views/layouts/app.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('Page loader is disabled for now. Re-enable when needed.', $contents);
        $this->assertStringNotContainsString('<div id="page-loader"', $contents);
    }
}
