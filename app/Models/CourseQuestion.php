<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseQuestion extends Model
{
    /** @use HasFactory<\Database\Factories\CourseQuestionFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'guest_name',
        'guest_email',
        'title',
        'content',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuestionAnswer::class, 'question_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(QuestionVote::class, 'question_id');
    }
}
