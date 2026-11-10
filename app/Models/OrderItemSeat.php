<?php

namespace App\Models;

use App\Enums\OrderItemSeatStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemSeat extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemSeatFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'course_id',
        'course_group_id',
        'purchaser_user_id',
        'invited_user_id',
        'invited_phone',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderItemSeatStatus::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseGroup(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchaser_user_id');
    }

    public function invitedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_user_id');
    }
}
