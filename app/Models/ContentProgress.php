<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentProgress extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'contentable_id',
        'contentable_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function contentable(): MorphTo
    {
        return $this->morphTo();
    }
}
