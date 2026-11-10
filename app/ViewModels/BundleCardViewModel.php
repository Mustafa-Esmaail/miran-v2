<?php

namespace App\ViewModels;

use App\Models\Bundle;

class BundleCardViewModel
{
    public static function fromBundle(Bundle $bundle, array $labels = []): array
    {
        $locale = app()->getLocale();

        $title = $bundle->getTranslation('title', $locale) ?? '';
        $category = $bundle->category?->getTranslation('name', $locale) ?? '';

        $imageUrl = $bundle->image_url ?? ($labels['fallback_image'] ?? 'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg');

        $finalPrice = $bundle->final_price ?? $bundle->price;
        $originalPrice = $bundle->price ?? null;
        $hasDiscount = $finalPrice !== null && $originalPrice !== null && $finalPrice < $originalPrice;
        $discountPercent = $hasDiscount && $originalPrice > 0
            ? (int) round((1 - ($finalPrice / $originalPrice)) * 100)
            : null;

        $priceLabel = $finalPrice !== null
            ? number_format($finalPrice)
            : __('web.courses.price_tbd');
        $originalPriceLabel = $hasDiscount
            ? number_format($originalPrice)
            : null;

        return [
            'id' => $bundle->id,
            'slug' => $bundle->slug,
            'title' => $title,
            'category' => $category,
            'imageUrl' => $imageUrl,
            'priceLabel' => $priceLabel,
            'originalPriceLabel' => $originalPriceLabel,
            'hasDiscount' => $hasDiscount,
            'badge' => $discountPercent ? "-{$discountPercent}%" : null,
            'coursesCount' => $bundle->courses_count ?? $bundle->courses()->count(),
        ];
    }

    public static function fromBundles($bundles, array $labels = []): array
    {
        return $bundles->map(fn (Bundle $bundle) => self::fromBundle($bundle, $labels))->all();
    }
}
