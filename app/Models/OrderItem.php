<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'purchasable_type',
        'purchasable_id',
        'title_snapshot',
        'currency',
        'metadata_snapshot',
        'price_amount',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'int',
            'quantity' => 'int',
            'metadata_snapshot' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (OrderItem $item) {
            if (! $item->purchasable_type || ! $item->purchasable_id) {
                throw new InvalidArgumentException(
                    'OrderItem must have a purchasable_type and purchasable_id.'
                );
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function purchasable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Expand each item to one row per course group it covers: the group
     * itself for a CourseGroup item, every selected group for a Bundle.
     */
    public function scopeWithCourseGroupRows(Builder $query): Builder
    {
        return $query
            ->leftJoin('course_groups', function ($join) {
                $join->on(function ($query) {
                    $query->where('order_items.purchasable_type', CourseGroup::class)
                        ->whereColumn('order_items.purchasable_id', 'course_groups.id');
                })
                    ->orOn(function ($query) {
                        $query->where('order_items.purchasable_type', Bundle::class)
                            ->whereRaw("JSON_CONTAINS(order_items.metadata_snapshot->'$.selected_course_group_ids', CAST(course_groups.id AS JSON))");
                    });
            });
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function seats(): HasMany
    {
        return $this->hasMany(OrderItemSeat::class);
    }

    public function getAssignedSeatsCountAttribute(): int
    {
        return $this->seats
            ->whereIn('status', [
                \App\Enums\OrderItemSeatStatus::Invited,
                \App\Enums\OrderItemSeatStatus::Enrolled,
            ])
            ->count();
    }

    public function getAvailableSeatsCountAttribute(): int
    {
        return $this->seats
            ->where('status', \App\Enums\OrderItemSeatStatus::Unassigned)
            ->count();
    }

    /**
     * Sum quantities for a filtered order-items query, counting each bundle
     * item once per course it contains rather than as a single unit.
     */
    public static function sumEffectiveQuantity(Builder $query): int
    {
        return $query
            ->get(['purchasable_type', 'purchasable_id', 'quantity', 'metadata_snapshot'])
            ->sum(function (self $item): int {
                if ($item->purchasable_type !== Bundle::class) {
                    return $item->quantity;
                }

                $bundleCourseCount = data_get($item->metadata_snapshot, 'bundle_course_count')
                    ?? Bundle::query()->find($item->purchasable_id)?->courses()->count()
                    ?? 1;

                return $item->quantity * $bundleCourseCount;
            });
    }
}
