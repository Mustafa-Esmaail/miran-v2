<?php

namespace App\Models;

use App\Enums\CommissionLedgerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionLedger extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_attribution_id',
        'employee_id',
        'gross_amount',
        'commission_rate',
        'commission_amount',
        'period',
        'status',
        'approved_at',
        'paid_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommissionLedgerStatus::class,
            'gross_amount' => 'int',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'int',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function orderAttribution(): BelongsTo
    {
        return $this->belongsTo(OrderAttribution::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
