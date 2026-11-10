<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoomSessionAttendance extends Model
{
    /** @use HasFactory<\Database\Factories\ZoomSessionAttendanceFactory> */
    use HasFactory;

    protected $fillable = [
        'course_group_session_id',
        'user_id',
        'enrollment_id',
        'zoom_meeting_id',
        'participant_name',
        'participant_email',
        'joined_at',
        'left_at',
        'duration_minutes',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
            'duration_minutes' => 'int',
            'raw_payload' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(CourseGroupSession::class, 'course_group_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
}
