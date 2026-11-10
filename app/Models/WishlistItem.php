<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class WishlistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wishable_type',
        'wishable_id',
    ];

    protected static function booted(): void
    {
        static::saving(function (WishlistItem $item) {
            if (! $item->wishable_type || ! $item->wishable_id) {
                throw new InvalidArgumentException(
                    'WishlistItem must have a wishable_type and wishable_id.'
                );
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function wishable(): MorphTo
    {
        return $this->morphTo();
    }
}
