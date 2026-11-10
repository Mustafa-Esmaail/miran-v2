<?php

namespace Tests\Feature;

use Tests\TestCase;

class TopbarMobileVisibilityTest extends TestCase
{
    public function test_topbar_hides_left_and_right_text_on_mobile(): void
    {
        $contents = file_get_contents(base_path('resources/views/components/site/topbar.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('hidden text-white lg:inline', $contents);
    }
}
