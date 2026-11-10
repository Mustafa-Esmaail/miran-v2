<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\Settings\SettingsService;
use App\ViewModels\HomeViewModel;
use Illuminate\View\View;

class HomeController
{
    public function __invoke(): View
    {
        $categories = app(CategoryRepositoryInterface::class)->getHomeCategories();
        $settings = app(SettingsService::class);
        $locale = app()->getLocale();

        return view('home', [
            'vm' => HomeViewModel::make($settings, $locale, $categories),
        ]);
    }
}
