<?php

namespace App\ViewModels;

use App\Models\Course;
use Illuminate\Support\Str;

class CourseCardViewModel
{
    public static function fromCourse(Course $course, array $labels = [], array $options = []): array
    {
        $locale = app()->getLocale();

        $title = method_exists($course, 'getTranslation')
            ? $course->getTranslation('title', $locale)
            : ($course->title ?? '');
        $summary = method_exists($course, 'getTranslation')
            ? $course->getTranslation('summary', $locale)
            : ($course->summary ?? '');
        $category = $course->category?->getTranslation('name', $locale) ?? '';

        $groups = $course->relationLoaded('groups') ? $course->groups : null;
        $group = $groups?->sortBy('start_at')->first();
        $startAt = $group?->start_at?->format('Y-m-d');
        $lessonsCount = $course->lessons_count
            ?? ($course->relationLoaded('lessons') ? $course->lessons->count() : null);
        $durationHours = $course->duration_hours ?? null;

        $originalPrice = $course->price;
        $displayPrice = $course->is_free
            ? 0
            : ($originalPrice !== null ? $course->calculateDiscountedPrice((float) $originalPrice) : null);

        $showDiscount = ! $course->is_free && $displayPrice !== null && $originalPrice !== null && $displayPrice < $originalPrice;
        $discountPercent = $showDiscount && $originalPrice > 0 ? (int) round((1 - ($displayPrice / $originalPrice)) * 100) : null;

        $priceLabel = $course->is_free
            ? __('web.courses.free')
            : ($displayPrice != null
            ? number_format($displayPrice)
            : __('web.courses.price_tbd'));
        $originalPriceLabel = $showDiscount && $originalPrice !== null
            ? number_format($originalPrice)
            : null;

        $metaRight = $options['meta_right'] ?? ($startAt
            ? __('web.courses.next_start', ['date' => $startAt])
            : __('web.courses.start_tbd'));

        $imageUrl = $course->image_url ?? ($options['fallback_image'] ?? 'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg');

        $badge = null;
        if ($discountPercent && ! empty($labels['discount_badge'])) {
            $badge = str_replace(':percent', $discountPercent, $labels['discount_badge']);
        }

        $summaryLimit = (int) ($options['summary_limit'] ?? 90);
        $summaryPreview = Str::limit(strip_tags($summary), $summaryLimit);

        $courseLevelValue = $course->course_level instanceof \BackedEnum
            ? $course->course_level->value
            : ($course->course_level ?? null);
        $levelLabel = $courseLevelValue
            ? __('web.courses.level_'.$courseLevelValue)
            : __('web.courses.level_beginner');
        $reviewsCount = (int) ($course->reviews_count ?? 0);
        $ratingAverage = $course->reviews_avg_rating !== null ? (float) $course->reviews_avg_rating : null;
        if ($reviewsCount === 0) {
            $ratingAverage = 5.0;
        }
        $enrollmentsCount = (int) ($course->enrollments_count ?? 0);
        $ratingValue = number_format((float) ($ratingAverage ?? 0), 1);
        $enrollmentsLabel = $enrollmentsCount > 0 ? number_format($enrollmentsCount).'+' : null;

        return [
            'id' => $course->id,
            'slug' => $course->slug,
            'title' => $title,
            'summary' => $summaryPreview,
            'category' => $category,
            'imageUrl' => $imageUrl,
            'priceLabel' => $priceLabel,
            'metaRight' => $metaRight,
            'badge' => $badge,
            'courseUrl' => route('courses.show', $course->slug),
            'lessonsCount' => $lessonsCount,
            'durationHours' => $durationHours,
            'levelLabel' => $levelLabel,
            'ratingAverage' => $ratingAverage,
            'ratingValue' => $ratingValue,
            'reviewsCount' => $reviewsCount,
            'enrollmentsCount' => $enrollmentsCount,
            'enrollmentsLabel' => $enrollmentsLabel,
            'originalPriceLabel' => $originalPriceLabel,
        ];
    }

    public static function fromCourses($courses, array $labels = [], array $options = []): array
    {
        return $courses->map(fn (Course $course) => self::fromCourse($course, $labels, $options))->all();
    }
}
