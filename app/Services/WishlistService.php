<?php

namespace App\Services;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\WishlistItem;
use App\Support\Purchasables\PurchasableResolver;
use Illuminate\Support\Facades\Schema;

class WishlistService
{
    public function __construct(private readonly PurchasableResolver $purchasables) {}

    public function toggle(int $userId, string $wishableType, int $wishableId): bool
    {
        $this->purchasables->resolveWishable($wishableType, $wishableId);

        $existingQuery = WishlistItem::query()->where('user_id', $userId);

        if ($wishableType === Course::class) {
            $existingQuery->where(function ($query) use ($wishableType, $wishableId) {
                $query->where(function ($query) use ($wishableType, $wishableId) {
                    $query->where('wishable_type', $wishableType)
                        ->where('wishable_id', $wishableId);
                });

                if ($this->hasLegacyCourseId()) {
                    $query->orWhere('course_id', $wishableId);
                }
            });
        } elseif ($wishableType === Bundle::class) {
            $existingQuery->where(function ($query) use ($wishableType, $wishableId) {
                $query->where(function ($query) use ($wishableType, $wishableId) {
                    $query->where('wishable_type', $wishableType)
                        ->where('wishable_id', $wishableId);
                });

                if ($this->hasLegacyBundleId()) {
                    $query->orWhere('bundle_id', $wishableId);
                }
            });
        } else {
            $existingQuery->where('wishable_type', $wishableType)
                ->where('wishable_id', $wishableId);
        }

        $existing = $existingQuery->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        if ($wishableType === Course::class) {
            if ($this->hasLegacyCourseId()) {
                WishlistItem::query()->updateOrCreate(
                    [
                        'user_id' => $userId,
                        'course_id' => $wishableId,
                    ],
                    [
                        'wishable_type' => $wishableType,
                        'wishable_id' => $wishableId,
                    ]
                );
            } else {
                WishlistItem::query()->updateOrCreate(
                    [
                        'user_id' => $userId,
                        'wishable_type' => $wishableType,
                        'wishable_id' => $wishableId,
                    ],
                    []
                );
            }

            return true;
        }

        if ($wishableType === Bundle::class) {
            if ($this->hasLegacyBundleId()) {
                WishlistItem::query()->updateOrCreate(
                    [
                        'user_id' => $userId,
                        'bundle_id' => $wishableId,
                    ],
                    [
                        'wishable_type' => $wishableType,
                        'wishable_id' => $wishableId,
                    ]
                );
            } else {
                WishlistItem::query()->updateOrCreate(
                    [
                        'user_id' => $userId,
                        'wishable_type' => $wishableType,
                        'wishable_id' => $wishableId,
                    ],
                    []
                );
            }

            return true;
        }

        WishlistItem::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'wishable_type' => $wishableType,
                'wishable_id' => $wishableId,
            ],
            []
        );

        return true;
    }

    public function isWishlisted(int $userId, string $wishableType, int $wishableId): bool
    {
        if ($wishableType === Course::class) {
            return WishlistItem::query()
                ->where('user_id', $userId)
                ->where(function ($query) use ($wishableType, $wishableId) {
                    $query->where(function ($query) use ($wishableType, $wishableId) {
                        $query->where('wishable_type', $wishableType)
                            ->where('wishable_id', $wishableId);
                    });

                    if ($this->hasLegacyCourseId()) {
                        $query->orWhere('course_id', $wishableId);
                    }
                })
                ->exists();
        }

        if ($wishableType === Bundle::class) {
            return WishlistItem::query()
                ->where('user_id', $userId)
                ->where(function ($query) use ($wishableType, $wishableId) {
                    $query->where(function ($query) use ($wishableType, $wishableId) {
                        $query->where('wishable_type', $wishableType)
                            ->where('wishable_id', $wishableId);
                    });

                    if ($this->hasLegacyBundleId()) {
                        $query->orWhere('bundle_id', $wishableId);
                    }
                })
                ->exists();
        }

        return WishlistItem::query()
            ->where('user_id', $userId)
            ->where('wishable_type', $wishableType)
            ->where('wishable_id', $wishableId)
            ->exists();
    }

    public function getWishlistedIds(int $userId, string $wishableType): array
    {
        if ($wishableType === Course::class) {
            if ($this->hasLegacyCourseId()) {
                return WishlistItem::query()
                    ->where('user_id', $userId)
                    ->get(['wishable_id', 'course_id'])
                    ->map(fn (WishlistItem $item) => $item->wishable_id ?? $item->course_id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            return WishlistItem::query()
                ->where('user_id', $userId)
                ->where('wishable_type', $wishableType)
                ->pluck('wishable_id')
                ->all();
        }

        if ($wishableType === Bundle::class) {
            if ($this->hasLegacyBundleId()) {
                return WishlistItem::query()
                    ->where('user_id', $userId)
                    ->get(['wishable_id', 'bundle_id'])
                    ->map(fn (WishlistItem $item) => $item->wishable_id ?? $item->bundle_id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            return WishlistItem::query()
                ->where('user_id', $userId)
                ->where('wishable_type', $wishableType)
                ->pluck('wishable_id')
                ->all();
        }

        return WishlistItem::query()
            ->where('user_id', $userId)
            ->where('wishable_type', $wishableType)
            ->pluck('wishable_id')
            ->all();
    }

    private function hasLegacyCourseId(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $cached = Schema::hasColumn('wishlist_items', 'course_id');

        return $cached;
    }

    private function hasLegacyBundleId(): bool
    {
        static $cached = null;

        if ($cached !== null) {
            return $cached;
        }

        $cached = Schema::hasColumn('wishlist_items', 'bundle_id');

        return $cached;
    }
}
