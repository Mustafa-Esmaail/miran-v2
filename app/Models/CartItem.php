<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'purchasable_type',
        'purchasable_id',
        'price_amount',
        'quantity',
        'selection_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'int',
            'quantity' => 'int',
            'selection_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CartItem $item) {
            if (! $item->purchasable_type || ! $item->purchasable_id) {
                throw new InvalidArgumentException(
                    'CartItem must have a purchasable_type and purchasable_id.'
                );
            }
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isBundle(): bool
    {
        return $this->purchasable_type === Bundle::class;
    }

    public function isCourseGroup(): bool
    {
        return $this->purchasable_type === CourseGroup::class;
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->purchasable instanceof Bundle) {
            return $this->purchasable->getTranslation('title', app()->getLocale()) ?? '';
        }

        if ($this->purchasable instanceof CourseGroup) {
            return $this->purchasable->course?->getTranslation('title', app()->getLocale())
                ?? $this->purchasable->getTranslation('title', app()->getLocale())
                ?? '';
        }

        return '';
    }

    public function getUnitPriceAmountAttribute(): int
    {
        return $this->price_amount;
    }
}
