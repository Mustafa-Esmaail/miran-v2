<?php

namespace App\Observers;

use App\Models\MenuItem;
use App\Services\Settings\MenuService;

class MenuItemObserver
{
    public function saved(MenuItem $menuItem): void
    {
        app(MenuService::class)->clearCache();
    }

    public function deleted(MenuItem $menuItem): void
    {
        app(MenuService::class)->clearCache();
    }
}
