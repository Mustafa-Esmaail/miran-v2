<?php

namespace Tests\Feature;

use Tests\TestCase;

class HeaderSearchAndMobileActionsTest extends TestCase
{
    public function test_header_uses_desktop_search_input_and_mobile_search_toggle(): void
    {
        $contents = file_get_contents(base_path('resources/views/components/site/header.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('form action="{{ route(\'courses.search\') }}" method="GET" class="hidden lg:flex"', $contents);
        $this->assertStringContainsString('x-data="{ mobileOpen: false, mobileSearchOpen: false }"', $contents);
        $this->assertStringContainsString('x-show="mobileSearchOpen"', $contents);
    }

    public function test_header_hides_wishlist_on_mobile_and_shows_mobile_profile_icon(): void
    {
        $contents = file_get_contents(base_path('resources/views/components/site/header.blade.php'));

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('lg:inline-flex', $contents);
        $this->assertStringContainsString('route(\'dashboard.wishlist\')', $contents);
        $this->assertStringContainsString('auth(\'web\')->check() ? route(\'dashboard\') : route(\'login\')', $contents);
        $this->assertStringContainsString('lg:hidden', $contents);
    }
}
