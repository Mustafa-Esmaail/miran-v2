<?php

namespace App\Models;

use App\Enums\DealStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Deal extends Model
{
    /** @use HasFactory<\Database\Factories\DealFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'service_request_id',
        'title',
        'stage',
        'value',
        'probability',
        'expected_close_date',
        'employee_id',
        'lost_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stage' => DealStage::class,
            'value' => 'integer',
            'probability' => 'integer',
            'expected_close_date' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function crmActivities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'entity');
    }

    public function crmTasks(): MorphMany
    {
        return $this->morphMany(CrmTask::class, 'entity');
    }
}
