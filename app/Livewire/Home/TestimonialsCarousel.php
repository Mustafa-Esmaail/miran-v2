<?php

namespace App\Livewire\Home;

use App\Services\Settings\SettingsService;
use App\Support\S3Url;
use Illuminate\View\View;
use Livewire\Component;

class TestimonialsCarousel extends Component
{
    public array $testimonials = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);
        $locale = app()->getLocale();
        $testimonials = $settings->getValue('home.testimonials', []);
        $testimonials = is_array($testimonials) ? $testimonials : [];

        if ($testimonials === []) {
            $testimonials = trans('web.home.testimonials');
            $testimonials = is_array($testimonials) ? $testimonials : [];
        }

        $this->testimonials = collect($testimonials)->map(function ($item) use ($locale) {
            $quote = $item["quote_{$locale}"] ?? $item['quote_en'] ?? $item['quote_ar'] ?? ($item['quote'] ?? '');
            $name = $item["name_{$locale}"] ?? $item['name_en'] ?? $item['name_ar'] ?? ($item['name'] ?? '');
            $role = $item["role_{$locale}"] ?? $item['role_en'] ?? $item['role_ar'] ?? ($item['role'] ?? '');
            $avatar = $item['avatar'] ?? '';
            if ($avatar && ! str_starts_with($avatar, 'http')) {
                $avatar = S3Url::temporary($avatar);
            }

            return [
                'quote' => $quote,
                'name' => $name,
                'role' => $role,
                'avatar' => $avatar,
            ];
        })->filter(fn ($item) => $item['quote'] !== '')->values()->all();
    }

    public function render(): View
    {
        return view('livewire.home.testimonials-carousel');
    }
}
