<?php

namespace App\Livewire\Home;

use App\Models\Category;
use App\Services\Settings\SettingsService;
use App\Support\S3Url;
use Illuminate\View\View;
use Livewire\Component;

class HeroSlider extends Component
{
    public array $slides = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);
        $locale = app()->getLocale();
        $slides = $settings->getValue('home.hero_slides', []);
        $slides = is_array($slides) ? $slides : [];

        if ($slides === []) {
            $slides = trans('web.home.hero_slides');
            $slides = is_array($slides) ? $slides : [];
        }

        $this->slides = collect($slides)->map(function ($slide) use ($locale) {
            $title = $slide["title_{$locale}"] ?? $slide['title_en'] ?? $slide['title_ar'] ?? ($slide['title'] ?? '');
            $subtitle = $slide["subtitle_{$locale}"] ?? $slide['subtitle_en'] ?? $slide['subtitle_ar'] ?? ($slide['subtitle'] ?? '');
            $image = $slide['image'] ?? '';
            if ($image && ! str_starts_with($image, 'http')) {
                $image = S3Url::temporary($image);
            }

            return [
                'title' => $title,
                'subtitle' => $subtitle,
                'image' => $image,
            ];
        })->filter(fn ($slide) => $slide['title'] || $slide['image'])->values()->all();
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        return view('livewire.home.hero-slider', [
            'categories' => Category::query()
                ->where('is_active', true)
                ->orderBy("name->{$locale}")
                ->get(),
        ]);
    }
}
