<?php

namespace App\Support\Purchasables;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Repositories\Contracts\CourseGroupRepositoryInterface;
use App\Services\PricingService;
use App\Support\S3Url;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class PurchasableResolver
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly CourseGroupRepositoryInterface $groups,
    ) {}

    /**
     * @return array<class-string<Model>>
     */
    public function purchasableTypes(): array
    {
        return [
            CourseGroup::class,
            Bundle::class,
        ];
    }

    /**
     * @return array<class-string<Model>>
     */
    public function wishableTypes(): array
    {
        return [
            Course::class,
            Bundle::class,
        ];
    }

    public function resolvePurchasable(string $type, int $id): Model
    {
        $this->assertAllowedType($type, $this->purchasableTypes());

        return match ($type) {
            Bundle::class => Bundle::query()->with(['discounts', 'courses'])->findOrFail($id),
            CourseGroup::class => CourseGroup::query()->with(['course.discounts'])->findOrFail($id),
            default => throw new ModelNotFoundException,
        };
    }

    public function resolveWishable(string $type, int $id): Model
    {
        $this->assertAllowedType($type, $this->wishableTypes());

        return match ($type) {
            Bundle::class => Bundle::query()->findOrFail($id),
            Course::class => Course::query()->findOrFail($id),
            default => throw new ModelNotFoundException,
        };
    }

    public function getPriceAmount(Model $purchasable): int
    {
        if ($purchasable instanceof Bundle) {
            $price = (float) ($purchasable->price ?? 0);

            return (int) round($this->pricing->calculateBundleFinalPrice($purchasable, $price));
        }

        if ($purchasable instanceof CourseGroup) {
            if ($purchasable->course?->is_free) {
                return 0;
            }

            $basePrice = (float) ($purchasable->course?->price ?? 0);

            return $purchasable->course
                ? (int) round($this->pricing->calculateCourseFinalPrice($purchasable->course, $basePrice))
                : (int) round($basePrice);
        }

        throw new InvalidArgumentException('Unsupported purchasable type.');
    }

    public function getDisplayLabel(Model $purchasable): string
    {
        $locale = app()->getLocale();

        if ($purchasable instanceof Bundle) {
            return $purchasable->getTranslation('title', $locale) ?? '';
        }

        if ($purchasable instanceof CourseGroup) {
            return $purchasable->course?->getTranslation('title', $locale)
                ?? $purchasable->getTranslation('title', $locale)
                ?? '';
        }

        return '';
    }

    public function getSubtitle(Model $purchasable): string
    {
        if ($purchasable instanceof Bundle) {
            $courseCount = $purchasable->relationLoaded('courses')
                ? $purchasable->courses->count()
                : $purchasable->courses()->count();
            $countLabel = __('web.bundles.course_count', ['count' => $courseCount]);

            return __('web.bundles.title').' · '.$countLabel;
        }

        if ($purchasable instanceof CourseGroup) {
            $groupTitle = $purchasable->getTranslation('title', app()->getLocale()) ?? '';

            return $groupTitle !== ''
                ? __('web.cart.course_group').' · '.$groupTitle
                : __('web.cart.course_group');
        }

        return '';
    }

    public function getThumbnailPath(Model $purchasable): ?string
    {
        return $this->getImagePath($purchasable);
    }

    public function getImageUrl(Model $purchasable): ?string
    {
        $path = $this->getImagePath($purchasable);
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '//')) {
            return $path;
        }

        return S3Url::temporary($path);
    }

    public function getImagePath(Model $purchasable): ?string
    {
        if ($purchasable instanceof Bundle) {
            return $purchasable->image_path;
        }

        if ($purchasable instanceof CourseGroup) {
            return $purchasable->course?->image_path;
        }

        return null;
    }

    public function getTypeLabel(Model $purchasable): string
    {
        return match (true) {
            $purchasable instanceof Bundle => 'bundle',
            $purchasable instanceof CourseGroup => 'course_group',
            default => 'unknown',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function buildMetadataSnapshot(Model $purchasable): array
    {
        $metadata = [
            'type_label' => $this->getTypeLabel($purchasable),
            'subtitle' => $this->getSubtitle($purchasable),
            'image_path' => $this->getImagePath($purchasable),
        ];

        if ($purchasable instanceof CourseGroup) {
            $metadata['course_group_id'] = $purchasable->id;
            $metadata['course_id'] = $purchasable->course_id;
            $metadata['course_slug'] = $purchasable->course?->slug;
        }

        if ($purchasable instanceof Bundle) {
            $metadata['bundle_id'] = $purchasable->id;
            $metadata['bundle_course_count'] = $purchasable->courses()->count();
        }

        return $metadata;
    }

    public function isAvailable(Model $purchasable): bool
    {
        if ($purchasable instanceof Bundle) {
            return (bool) $purchasable->is_active;
        }

        if ($purchasable instanceof CourseGroup) {
            return $this->groups->availableForEnrollment($purchasable->id);
        }

        return false;
    }

    /**
     * @param  array<class-string<Model>>  $allowed
     */
    private function assertAllowedType(string $type, array $allowed): void
    {
        if (! in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported type provided.');
        }
    }
}
