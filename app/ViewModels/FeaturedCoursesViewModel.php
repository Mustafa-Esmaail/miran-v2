<?php

namespace App\ViewModels;

class FeaturedCoursesViewModel
{
    public function __construct(
        public readonly string $title,
        public readonly string $subtitle,
        public readonly string $priceRange,
        public readonly string $discountLabel,
        public readonly string $quickView,
        public readonly string $quickViewHint,
        public readonly string $viewCourse,
    ) {}

    public static function make(?string $title, ?string $subtitle, callable $getText): self
    {
        return new self(
            title: $title ?: $getText('title', __('web.home.most_requested_title')),
            subtitle: $subtitle ?: $getText('subtitle', __('web.home.most_requested_subtitle')),
            priceRange: $getText('price_range', __('web.home.price_range')),
            discountLabel: $getText('discount_badge', __('web.home.discount_badge')),
            quickView: $getText('quick_view', __('web.home.quick_view')),
            quickViewHint: $getText('quick_view_hint', __('web.home.quick_view_hint')),
            viewCourse: $getText('view_course', __('web.home.view_course')),
        );
    }
}
