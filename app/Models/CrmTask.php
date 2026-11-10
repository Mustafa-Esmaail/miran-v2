<?php

namespace App\Models;

use App\Enums\CrmTaskPriority;
use App\Enums\CrmTaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CrmTask extends Model
{
    /** @use HasFactory<\Database\Factories\CrmTaskFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $task): void {
            if ($task->status === CrmTaskStatus::Done || $task->status === CrmTaskStatus::Done->value) {
                $task->is_done = true;
                $task->completed_at ??= now();

                return;
            }

            $task->is_done = false;
            $task->completed_at = null;
        });
    }

    protected $fillable = [
        'entity_type',
        'entity_id',
        'title',
        'due_at',
        'priority',
        'status',
        'is_done',
        'employee_id',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => CrmTaskPriority::class,
            'status' => CrmTaskStatus::class,
            'is_done' => 'bool',
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
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
