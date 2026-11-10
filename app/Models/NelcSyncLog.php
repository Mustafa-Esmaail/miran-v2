<?php

namespace App\Models;

use App\Enums\NelcSyncStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NelcSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'user_id',
        'course_id',
        'course_group_id',
        'reference_type',
        'reference_id',
        'payload',
        'status',
        'response',
        'attempts',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => NelcSyncStatus::class,
            'attempts' => 'int',
            'synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseGroup(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
