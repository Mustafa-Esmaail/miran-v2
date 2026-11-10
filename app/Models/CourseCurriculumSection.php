<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class CourseCurriculumSection extends Model
{
    /** @use HasFactory<\Database\Factories\CourseCurriculumSectionFactory> */
    use HasFactory, HasTranslations;

    protected $fillable = [
        'course_id',
        'course_group_id',
        'title',
        'description',
        'sort_order',
        'week',
        'badge',
        'outcome',
        'bullets',
        'is_featured',
        'is_capstone',
    ];

    public array $translatable = ['title', 'description', 'badge', 'outcome'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'int',
            'week' => 'int',
            'bullets' => 'array',
            'is_featured' => 'bool',
            'is_capstone' => 'bool',
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

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CourseAttachment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(CourseGroupSession::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class, 'course_curriculum_section_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'course_curriculum_section_id');
    }
}
