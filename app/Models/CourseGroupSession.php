<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CourseGroupSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_group_id',
        'course_curriculum_section_id',
        'zoom_host_user_id',
        'zoom_host_email',
        'title',
        'content',
        'start_at',
        'duration_minutes',
        'join_url',
        'recording_url',
        'zoom_meeting_id',
        'zoom_meeting_uuid',
        'zoom_start_url',
        'zoom_synced_at',
        'zoom_actual_started_at',
        'zoom_actual_ended_at',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'duration_minutes' => 'int',
            'zoom_synced_at' => 'datetime',
            'zoom_actual_started_at' => 'datetime',
            'zoom_actual_ended_at' => 'datetime',
            'attachments' => 'array',
        ];
    }

    protected function asDateTime($value): Carbon
    {
        try {
            return parent::asDateTime($value);
        } catch (\Throwable) {
            // Treat corrupted datetime values as "not equivalent" so they get overwritten on save
            return Carbon::now();
        }
    }

    public function courseGroup(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function curriculumSection(): BelongsTo
    {
        return $this->belongsTo(CourseCurriculumSection::class, 'course_curriculum_section_id');
    }

    public function zoomHostUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'zoom_host_user_id');
    }

    public function zoomAttendances(): HasMany
    {
        return $this->hasMany(ZoomSessionAttendance::class);
    }

    public function liveAttendances(): HasMany
    {
        return $this->hasMany(ZoomLiveAttendance::class);
    }

    public function groupEnrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_group_id', 'course_group_id');
    }

    public function attendedEnrollments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Enrollment::class,
            ZoomSessionAttendance::class,
            'course_group_session_id',
            'id',
            'id',
            'enrollment_id',
        )->whereNotNull('zoom_session_attendances.enrollment_id');
    }

    public function absentEnrollments(): Collection
    {
        $attendedIds = $this->zoomAttendances()
            ->whereNotNull('enrollment_id')
            ->pluck('enrollment_id');

        return $this->courseGroup?->enrollments()
            ->whereNotIn('id', $attendedIds)
            ->with('user')
            ->get() ?? new Collection;
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_at', '>', now());
    }

    public function scopeEnded(Builder $query): Builder
    {
        return $query->where('start_at', '<=', now());
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query
            ->where('start_at', '<=', now())
            ->whereRaw('DATE_ADD(start_at, INTERVAL duration_minutes MINUTE) >= ?', [now()]);
    }
}
