<?php

namespace App\Models;

use App\Enums\CouponTargetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'target_type',
        'target_id',
    ];

    protected function casts(): array
    {
        return [
            'target_type' => CouponTargetType::class,
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
