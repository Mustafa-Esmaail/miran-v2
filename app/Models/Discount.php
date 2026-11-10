<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Discount extends Model
{
    use HasFactory;

    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENTAGE = 'percentage';

    protected $fillable = [
        'type',
        'amount',
        'max_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'is_active' => 'bool',
        ];
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'discount_courses')->withTimestamps();
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class, 'discount_bundles')->withTimestamps();
    }

    public function calculateDiscountAmount(float $price): float
    {
        $amount = $this->type === self::TYPE_PERCENTAGE
            ? ($price * ((float) $this->amount / 100))
            : (float) $this->amount;

        if ($this->max_amount !== null && $amount > (float) $this->max_amount) {
            $amount = (float) $this->max_amount;
        }

        return max(0, $amount);
    }
}
