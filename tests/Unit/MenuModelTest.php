<?php

namespace Tests\Unit;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class MenuModelTest extends TestCase
{
    public function test_menu_items_relation_is_available(): void
    {
        $menu = new Menu;

        $this->assertInstanceOf(HasMany::class, $menu->menuItems());
    }
}
