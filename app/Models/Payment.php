<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Concerns\HasDeletedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasDeletedBy;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'status',
        'amount',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'int',
            'payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
