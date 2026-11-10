<?php

namespace App\Models;

use App\Enums\CrmActivityOutcome;
use App\Enums\CrmActivityType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmActivity extends Model
{
    /** @use HasFactory<\Database\Factories\CrmActivityFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'type',
        'subject',
        'body',
        'outcome',
        'scheduled_at',
        'completed_at',
        'employee_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => CrmActivityType::class,
            'outcome' => CrmActivityOutcome::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
