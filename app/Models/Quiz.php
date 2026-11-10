<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'course_group_id',
        'course_curriculum_section_id',
        'title',
        'passing_score',
        'allow_resubmit',
        'max_attempts',
    ];

    protected function casts(): array
    {
        return [
            'passing_score' => 'int',
            'allow_resubmit' => 'bool',
            'max_attempts' => 'int',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function courseGroup(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class);
    }

    public function curriculumSection(): BelongsTo
    {
        return $this->belongsTo(CourseCurriculumSection::class, 'course_curriculum_section_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
