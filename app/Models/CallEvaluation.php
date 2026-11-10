<?php

namespace App\Models;

use App\Services\ServiceRequestService;
use App\Support\CallQuality\CallScore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallEvaluation extends Model
{
    /** @use HasFactory<\Database\Factories\CallEvaluationFactory> */
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'service_request_id',
        'crm_activity_id',
        'call_phone',
        'call_date',
        'scores',
        'notes',
        'general_notes',
        'discussed_at',
        'discussed_by',
        'approved_at',
        'approved_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $evaluation): void {
            if (filled($evaluation->call_phone)) {
                $evaluation->call_phone = ServiceRequestService::normalizePhone($evaluation->call_phone) ?: $evaluation->call_phone;
            }

            $evaluation->applyScore();
        });
    }

    protected function casts(): array
    {
        return [
            'call_date' => 'date',
            'scores' => 'array',
            'notes' => 'array',
            'score_percent' => 'float',
            'discussed_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function crmActivity(): BelongsTo
    {
        return $this->belongsTo(CrmActivity::class);
    }

    public function discussedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discussed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeAwaitingDiscussion(Builder $query): Builder
    {
        return $query->whereNull('discussed_at');
    }

    public function scopeAwaitingApproval(Builder $query): Builder
    {
        return $query->whereNotNull('discussed_at')->whereNull('approved_at');
    }

    public function isDiscussed(): bool
    {
        return $this->discussed_at !== null;
    }

    public function isApproved(): bool
    {
        return $this->approved_at !== null;
    }

    /**
     * Store the score % and level from the current criteria weights.
     */
    public function applyScore(): void
    {
        $result = CallScore::calculate($this->scores ?? []);

        $this->score_percent = $result['percent'];
        $this->level = $result['level'];
    }

    /**
     * Re-score every evaluation after criteria weights or level thresholds change,
     * as the reference system recomputes old evaluations on the fly.
     */
    public static function recalculateAll(): int
    {
        $updated = 0;

        self::query()->chunkById(200, function ($evaluations) use (&$updated): void {
            foreach ($evaluations as $evaluation) {
                $evaluation->applyScore();

                if ($evaluation->isDirty(['score_percent', 'level'])) {
                    $evaluation->saveQuietly();
                    $updated++;
                }
            }
        });

        return $updated;
    }
}
