<?php

namespace App\Models;

use App\Enums\OrderAttributionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderAttribution extends Model
{
    /** @use HasFactory<\Database\Factories\OrderAttributionFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'service_request_id',
        'sales_user_id',
        'attribution_type',
        'credited_amount',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'attribution_type' => OrderAttributionType::class,
            'credited_amount' => 'int',
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function salesUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    public function commissionLedger(): HasOne
    {
        return $this->hasOne(CommissionLedger::class);
    }
}
