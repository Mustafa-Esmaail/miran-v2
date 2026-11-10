<?php

namespace App\Models;

use App\Enums\CouponAppliesTo;
use App\Enums\CouponType;
use App\Models\Concerns\HasDeletedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Coupon extends Model
{
    use HasDeletedBy, HasFactory, HasTranslations, SoftDeletes;

    protected $fillable = [
        'title',
        'code',
        'type',
        'applies_to',
        'value',
        'starts_at',
        'ends_at',
        'usage_limit',
        'per_user_limit',
        'min_order_amount',
        'min_items_count',
        'is_active',
        'employee_id',
    ];

    public array $translatable = ['title'];

    /**
     * For fixed coupons, value and min_order_amount are stored as SAR amounts.
     * For percentage coupons, value represents the percentage (e.g., 10 = 10%).
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'applies_to' => CouponAppliesTo::class,
            'value' => 'int',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'usage_limit' => 'int',
            'per_user_limit' => 'int',
            'min_order_amount' => 'int',
            'min_items_count' => 'int',
            'is_active' => 'bool',
        ];
    }

    public function targets(): HasMany
    {
        return $this->hasMany(CouponTarget::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $builder) {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $builder) {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }
}
