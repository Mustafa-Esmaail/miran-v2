<?php

namespace App\Observers;

use App\Models\Menu;
use App\Services\Settings\MenuService;

class MenuObserver
{
    public function saved(Menu $menu): void
    {
        app(MenuService::class)->clearCache();
    }

    public function deleted(Menu $menu): void
    {
        app(MenuService::class)->clearCache();
    }
}
