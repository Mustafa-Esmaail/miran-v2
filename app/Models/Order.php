<?php

namespace App\Models;

use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Models\Concerns\HasDeletedBy;
use App\Observers\OrderObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(OrderObserver::class)]
class Order extends Model
{
    use HasDeletedBy;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_phone',
        'order_no',
        'status',
        'currency',
        'subtotal_amount',
        'discount_amount',
        'total_amount',
        'coupon_id',
        'referred_by_employee_id',
        'marketing_source',
        'source',
        'notes',
        'sheet_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'source' => OrderSource::class,
            'subtotal_amount' => 'int',
            'discount_amount' => 'int',
            'total_amount' => 'int',
            'sheet_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function bankTransferReceipt(): HasOne
    {
        return $this->hasOne(BankTransferReceipt::class)->latestOfMany();
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function bankTransferReceipts(): HasMany
    {
        return $this->hasMany(BankTransferReceipt::class);
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function attributions(): HasMany
    {
        return $this->hasMany(OrderAttribution::class);
    }

    public function referredByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'referred_by_employee_id');
    }

    public function seats(): HasMany
    {
        return $this->hasMany(OrderItemSeat::class);
    }

    public function enrollments(): HasManyThrough
    {
        return $this->hasManyThrough(Enrollment::class, OrderItem::class, 'order_id', 'order_item_id', 'id', 'id');
    }

    public function crmActivities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'entity');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(OrderActivityLog::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(OrderNotice::class);
    }

    /**
     * @param  string[]|string|null  $source  a single source value, or a list of source values to match any of
     */
    public function scopeFilteredForReport(Builder $query, ?string $dateFrom, ?string $dateTo, ?string $paymentMethod, array|string|null $source = null, ?string $status = null): Builder
    {
        return $query
            ->where('source', '!=', OrderSource::Admin->value)
            ->when($dateFrom, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
            ->when($dateTo, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date))
            ->when($paymentMethod, function (Builder $q, string $method): Builder {
                if ($method === 'bank_transfer') {
                    return $q->where(fn (Builder $inner): Builder => $inner
                        ->whereHas('payments', fn (Builder $p): Builder => $p->where('provider', 'bank_transfer'))
                        ->orWhereHas('bankTransferReceipt'));
                }

                return $q->whereHas('payments', fn (Builder $p): Builder => $p->where('provider', $method));
            })
            ->when($source, fn (Builder $q, array|string $s): Builder => is_array($s) ? $q->whereIn('source', $s) : $q->where('source', $s))
            ->when($status, fn (Builder $q, string $s): Builder => $q->where('status', $s));
    }

    public function getPrimaryPaymentMethodAttribute(): ?string
    {
        if ($this->bankTransferReceipt || $this->payments->contains(fn (Payment $payment): bool => $payment->provider === 'bank_transfer')) {
            return 'bank_transfer';
        }

        return $this->payments->first()?->provider;
    }
}
