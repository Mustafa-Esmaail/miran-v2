<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CourseInstructor extends Pivot
{
    protected $table = 'course_group_instructors';

    protected $fillable = [
        'course_id',
        'course_group_id',
        'user_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            if (! $pivot->course_id && $pivot->course_group_id) {
                $pivot->course_id = CourseGroup::whereKey($pivot->course_group_id)->value('course_id');
            }
        });
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseGroup(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
