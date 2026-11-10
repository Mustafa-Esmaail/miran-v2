<?php

namespace App\Models;

use App\Support\S3Url;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Translatable\HasTranslations;

class Bundle extends Model
{
    use HasFactory, HasTranslations;

    /**
     * Cart and wishlist rows are polymorphic (no FK cascade), so they would otherwise point at a missing bundle.
     */
    protected static function booted(): void
    {
        static::deleted(function (self $bundle): void {
            $bundle->cartItems()->delete();
            $bundle->wishlistItems()->delete();
        });
    }

    public function getImagePathAttribute(): ?string
    {
        return $this->attributes['image_path'] ?? null;
    }

    public function getImageUrlAttribute(): ?string
    {
        $path = $this->image_path;
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '//')) {
            return $path;
        }

        return S3Url::temporary($path);
    }

    protected $fillable = [
        'category_id',
        'title',
        'summary',
        'description',
        'slug',
        'image_path',
        'price',
        'is_active',
    ];

    public array $translatable = ['title', 'summary', 'description'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'bool',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'bundle_courses')->withTimestamps();
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_bundles')->withTimestamps();
    }

    public function wishlistedBy(): MorphToMany
    {
        return $this->morphToMany(User::class, 'wishable', 'wishlist_items')->withTimestamps();
    }

    public function wishlistItems(): MorphMany
    {
        return $this->morphMany(WishlistItem::class, 'wishable');
    }

    public function landingPages(): MorphMany
    {
        return $this->morphMany(Page::class, 'landable');
    }

    public function cartItems(): MorphMany
    {
        return $this->morphMany(CartItem::class, 'purchasable');
    }

    public function orderItems(): MorphMany
    {
        return $this->morphMany(OrderItem::class, 'purchasable');
    }

    public function getFinalPriceAttribute(): ?float
    {
        $price = $this->price;
        if ($price === null) {
            return null;
        }

        return $this->calculateDiscountedPrice((float) $price);
    }

    public function getBestDiscountAmount(float $price): float
    {
        $discounts = $this->relationLoaded('discounts') ? $this->discounts : $this->discounts()->get();

        $best = 0.0;
        foreach ($discounts as $discount) {
            if (! $discount->is_active) {
                continue;
            }

            $amount = $discount->calculateDiscountAmount($price);
            if ($amount > $best) {
                $best = $amount;
            }
        }

        return $best;
    }

    public function calculateDiscountedPrice(float $price): float
    {
        $discountAmount = $this->getBestDiscountAmount($price);

        return max(0, $price - $discountAmount);
    }
}
