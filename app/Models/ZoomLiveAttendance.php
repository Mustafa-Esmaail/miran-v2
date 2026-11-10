<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoomLiveAttendance extends Model
{
    protected $fillable = [
        'course_group_session_id',
        'user_id',
        'enrollment_id',
        'zoom_participant_id',
        'participant_name',
        'participant_email',
        'role',
        'status',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
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

    public function scopeStillInMeeting(Builder $query): Builder
    {
        return $query->whereNull('left_at');
    }

    public function scopeHosts(Builder $query): Builder
    {
        return $query->where('role', 'host');
    }

    public function scopeWaitingRoom(Builder $query): Builder
    {
        return $query->where('status', 'waiting_room');
    }

    public function scopePresent(Builder $query): Builder
    {
        return $query->whereIn('status', ['joined', 'joined_before_host']);
    }
}
