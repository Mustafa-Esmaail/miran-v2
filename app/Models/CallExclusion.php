<?php

namespace App\Models;

use App\Services\ServiceRequestService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A phone permanently removed from the call-audit queue ("follow-up" call,
 * not a first contact worth evaluating).
 */
class CallExclusion extends Model
{
    /** @use HasFactory<\Database\Factories\CallExclusionFactory> */
    use HasFactory;

    public const REASON_FOLLOW_UP = 'follow_up';

    protected $fillable = [
        'phone',
        'employee_id',
        'service_request_id',
        'reason',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $exclusion): void {
            $exclusion->phone = ServiceRequestService::normalizePhone((string) $exclusion->phone) ?: $exclusion->phone;
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
