<?php

namespace App\Repositories\Eloquent;

use App\Models\Menu;
use App\Repositories\Contracts\MenuRepositoryInterface;
use Illuminate\Support\Collection;

class MenuRepository implements MenuRepositoryInterface
{
    public function getActiveMenuByLocation(string $location): ?Menu
    {
        return Menu::query()
            ->where('location', $location)
            ->where('is_active', true)
            ->first();
    }

    public function getActiveItemsForLocation(string $location): Collection
    {
        $menu = Menu::query()
            ->where('location', $location)
            ->where('is_active', true)
            ->first();

        if (! $menu) {
            return collect();
        }

        return $menu->items()
            ->where('is_active', true)
            ->whereNull('parent_id')
            ->with(['page', 'children' => function ($query) {
                $query->where('is_active', true)->with('page');
            }])
            ->get();
    }
}
