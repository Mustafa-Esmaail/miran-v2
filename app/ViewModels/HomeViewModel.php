<?php

namespace App\ViewModels;

use App\Services\Settings\SettingsService;
use App\Support\S3Url;
use Illuminate\Support\Collection;

class HomeViewModel
{
    public function __construct(
        public readonly array $categories,
        public readonly string $aboutTitle,
        public readonly string $aboutSubtitle,
        public readonly string $aboutBody,
        public readonly string $aboutCardLabel,
        public readonly string $aboutCardTitle,
        public readonly string $aboutCardBody,
        public readonly array $aboutPoints,
        public readonly string $categoriesTitle,
        public readonly string $categoriesSubtitle,
        public readonly string $categoryLabel,
        public readonly string $viewCoursesLabel,
        public readonly string $contactRequestLabel,
        public readonly string $partnersTitle,
        public readonly string $partnersSubtitle,
        public readonly array $partnerLogos,
        public readonly string $whyTitle,
        public readonly string $whySubtitle,
        public readonly array $whyPoints,
        public readonly string $corporateTag,
        public readonly string $corporateTitle,
        public readonly string $corporateSubtitle,
        public readonly array $statsItems,
        public readonly string $contactEmail,
        public readonly string $complaintsTag,
        public readonly string $complaintsTitle,
        public readonly string $complaintsSubtitle,
        public readonly string $complaintsCta,
    ) {}

    public static function make(SettingsService $settings, string $locale, Collection $categories): self
    {
        $aboutPoints = self::pickPoints($settings->getValue('home.about_points', []), $locale, 'home.about_points');
        $whyPoints = self::pickPoints($settings->getValue('home.why_points', []), $locale, 'home.why_points');

        $partnerLogos = $settings->getValue('home.partners_logos', []);
        $partnerLogos = is_array($partnerLogos) ? $partnerLogos : [];
        $partnerLogos = collect($partnerLogos)
            ->map(fn ($logo) => $logo && ! str_starts_with($logo, 'http') ? S3Url::temporary($logo) : $logo)
            ->filter()
            ->values()
            ->all();
        if ($partnerLogos === []) {
            $partnerLogos = [
                'https://themiran.net/wp-content/uploads/2025/02/contact-mail_3095583-1.png',
                'https://themiran.net/wp-content/uploads/2023/11/New_Project-removebg-preview-e1699446752333.png',
                'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg',
                'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=400&q=80',
                'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=400&q=80',
            ];
        }

        $statsItems = $settings->getValue('home.stats_items', []);
        $statsItems = is_array($statsItems) ? $statsItems : [];
        $statsItems = collect($statsItems)->map(function ($item) use ($locale) {
            $label = $locale === 'ar' ? ($item['label_ar'] ?? '') : ($item['label_en'] ?? '');
            if ($label === '') {
                $label = $locale === 'ar'
                    ? ($item['label_ar'] ?? $item['label_en'] ?? '')
                    : ($item['label_en'] ?? $item['label_ar'] ?? '');
            }

            return [
                'value' => $item['value'] ?? '',
                'label' => $label,
            ];
        })->filter(fn ($item) => $item['label'] !== '' || $item['value'] !== '')->values()->all();
        if ($statsItems === []) {
            $statsItems = [
                ['value' => '+3,200', 'label' => __('web.home.stats.learners')],
                ['value' => '+180', 'label' => __('web.home.stats.entities')],
                ['value' => '+120', 'label' => __('web.home.stats.programs')],
            ];
        }

        return new self(
            categories: $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'courses_count' => $category->courses_count,
            ])->all(),
            aboutTitle: $settings->getTranslatable('home.about_title', $locale, __('web.home.about_title')),
            aboutSubtitle: $settings->getTranslatable('home.about_subtitle', $locale, __('web.home.about_subtitle')),
            aboutBody: $settings->getTranslatable('home.about_body', $locale, __('web.home.about_body')),
            aboutCardLabel: $settings->getTranslatable('home.about_card_label', $locale, __('web.home.about_card_label')),
            aboutCardTitle: $settings->getTranslatable('home.about_card_title', $locale, __('web.home.about_card_title')),
            aboutCardBody: $settings->getTranslatable('home.about_card_body', $locale, __('web.home.about_card_body')),
            aboutPoints: $aboutPoints,
            categoriesTitle: $settings->getTranslatable('home.categories_title', $locale, __('web.home.categories_title')),
            categoriesSubtitle: $settings->getTranslatable('home.categories_subtitle', $locale, __('web.home.categories_subtitle')),
            categoryLabel: $settings->getTranslatable('home.category_label', $locale, __('web.home.category_label')),
            viewCoursesLabel: $settings->getTranslatable('home.view_courses_label', $locale, __('web.home.view_courses')),
            contactRequestLabel: $settings->getTranslatable('home.contact_request_label', $locale, __('web.home.contact_request')),
            partnersTitle: $settings->getTranslatable('home.partners_title', $locale, __('web.home.partners_title')),
            partnersSubtitle: $settings->getTranslatable('home.partners_subtitle', $locale, __('web.home.partners_subtitle')),
            partnerLogos: $partnerLogos,
            whyTitle: $settings->getTranslatable('home.why_title', $locale, __('web.home.why_title')),
            whySubtitle: $settings->getTranslatable('home.why_subtitle', $locale, __('web.home.why_subtitle')),
            whyPoints: $whyPoints,
            corporateTag: $settings->getTranslatable('home.corporate_tag', $locale, __('web.home.corporate_tag')),
            corporateTitle: $settings->getTranslatable('home.corporate_title', $locale, __('web.home.corporate_title')),
            corporateSubtitle: $settings->getTranslatable('home.corporate_subtitle', $locale, __('web.home.corporate_subtitle')),
            statsItems: $statsItems,
            contactEmail: (string) $settings->getValue('contact.email', 'info@example.com'),
            complaintsTag: $settings->getTranslatable('home.complaints_tag', $locale, __('web.home.complaints_tag')),
            complaintsTitle: $settings->getTranslatable('home.complaints_title', $locale, __('web.home.complaints_title')),
            complaintsSubtitle: $settings->getTranslatable('home.complaints_subtitle', $locale, __('web.home.complaints_subtitle')),
            complaintsCta: $settings->getTranslatable('home.complaints_cta', $locale, __('web.home.complaints_cta')),
        );
    }

    private static function pickPoints($points, string $locale, string $fallbackKey): array
    {
        $points = is_array($points) && $points !== [] ? $points : (is_array(trans($fallbackKey)) ? trans($fallbackKey) : []);

        return collect($points)->map(function ($point) use ($locale) {
            $item = (array) $point;
            $title = $item["title_{$locale}"] ?? ($locale === 'ar' ? ($item['title_ar'] ?? null) : ($item['title_en'] ?? null));
            if (! $title) {
                $title = $item['title_en'] ?? $item['title_ar'] ?? $item['title'] ?? '';
            }

            $body = $item["body_{$locale}"] ?? ($locale === 'ar' ? ($item['body_ar'] ?? null) : ($item['body_en'] ?? null));
            if (! $body) {
                $body = $item['body_en'] ?? $item['body_ar'] ?? $item['body'] ?? '';
            }

            return [
                'title' => $title,
                'body' => $body,
            ];
        })->all();
    }
}
