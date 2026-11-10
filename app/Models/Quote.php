<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $fillable = [
        'deal_id',
        'quote_no',
        'status',
        'valid_until',
        'subtotal',
        'discount',
        'total',
        'items',
        'notes',
        'sent_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'subtotal' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'items' => 'array',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Quote $quote): void {
            if (empty($quote->quote_no)) {
                $quote->quote_no = 'QT-'.str_pad((Quote::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
