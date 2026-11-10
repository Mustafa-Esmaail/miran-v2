<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FooterMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_renders_browse_menu_items(): void
    {
        $menu = Menu::query()->create([
            'name' => 'Footer Browse',
            'location' => 'footer_browse',
            'is_active' => true,
        ]);

        MenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => ['en' => 'Browse Help'],
            'type' => 'custom',
            'url' => '/browse-help',
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $html = Blade::render('<x-site.footer />');

        $this->assertStringContainsString('Browse Help', $html);
    }
}
