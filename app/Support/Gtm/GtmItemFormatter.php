<?php

namespace App\Support\Gtm;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\OrderItem;

class GtmItemFormatter
{
    /**
     * Shared item id used by every dataLayer event and by ad-platform catalogs
     * (e.g. Snap/Meta product feeds), so "course_12" always means course #12.
     */
    public static function courseItemId(int $courseId): string
    {
        return 'course_'.$courseId;
    }

    public static function bundleItemId(int $bundleId): string
    {
        return 'bundle_'.$bundleId;
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromCourse(Course $course): array
    {
        return [
            'item_id' => self::courseItemId($course->id),
            'item_name' => $course->title,
            'item_category' => 'course',
            'price' => (int) round((float) $course->final_price),
            'quantity' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromPurchasable(CourseGroup|Bundle $purchasable, string $name, int $price, int $quantity): array
    {
        return [
            'item_id' => $purchasable instanceof CourseGroup
                ? self::courseItemId($purchasable->course_id)
                : self::bundleItemId($purchasable->id),
            'item_name' => $name,
            'item_category' => $purchasable instanceof CourseGroup ? 'course' : 'bundle',
            'price' => $price,
            'quantity' => $quantity,
        ];
    }

    /**
     * @param  array{purchasableId:int, courseId?:int|null, title:string, itemType:string, priceAmount:int, originalPriceAmount:int, quantity:int}  $item
     * @return array<string, mixed>
     */
    public static function fromCartItem(array $item): array
    {
        $original = $item['originalPriceAmount'] ?? $item['priceAmount'];
        $isBundle = $item['itemType'] === 'bundle';

        return [
            'item_id' => $isBundle
                ? self::bundleItemId($item['purchasableId'])
                : self::courseItemId($item['courseId'] ?? $item['purchasableId']),
            'item_name' => $item['title'],
            'item_category' => $isBundle ? 'bundle' : 'course',
            'price' => $item['priceAmount'],
            'discount' => max(0, $original - $item['priceAmount']),
            'quantity' => $item['quantity'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function fromCartItems(array $items): array
    {
        return array_map(fn (array $item) => self::fromCartItem($item), $items);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function valueOf(array $items): int
    {
        return (int) array_sum(array_map(
            fn (array $item) => $item['priceAmount'] * $item['quantity'],
            $items
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromOrderItem(OrderItem $item): array
    {
        $metadata = $item->metadata_snapshot ?? [];
        $isBundle = ($metadata['type_label'] ?? null) === 'bundle' || $item->purchasable_type === Bundle::class;

        return [
            'item_id' => $isBundle
                ? self::bundleItemId((int) ($metadata['bundle_id'] ?? $item->purchasable_id))
                : self::courseItemId((int) ($metadata['course_id'] ?? $item->purchasable_id)),
            'item_name' => $item->title_snapshot,
            'item_category' => $isBundle ? 'bundle' : 'course',
            'price' => $item->price_amount,
            'quantity' => $item->quantity,
        ];
    }

    /**
     * @param  iterable<OrderItem>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function fromOrderItems(iterable $items): array
    {
        return array_map(fn (OrderItem $item) => self::fromOrderItem($item), [...$items]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromEnrollment(Enrollment $enrollment): array
    {
        $enrollment->loadMissing('course');

        return [
            'item_id' => self::courseItemId($enrollment->course_id),
            'item_name' => $enrollment->course?->title,
            'item_category' => 'course',
        ];
    }

    /**
     * @param  iterable<Enrollment>  $enrollments
     * @return array<int, array<string, mixed>>
     */
    public static function fromEnrollments(iterable $enrollments): array
    {
        return array_map(fn (Enrollment $enrollment) => self::fromEnrollment($enrollment), [...$enrollments]);
    }
}
