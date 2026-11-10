<?php

namespace App\Services\Settings;

use App\Repositories\Contracts\MenuRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MenuService
{
    private const HEADER_CACHE_KEY = 'menus.header';

    public function __construct(private readonly MenuRepositoryInterface $menus) {}

    public function getHeaderMenuItems(): Collection
    {
        return Cache::remember(self::HEADER_CACHE_KEY, now()->addMinutes(30), function () {
            return $this->menus->getActiveItemsForLocation('header');
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::HEADER_CACHE_KEY);
    }
}
