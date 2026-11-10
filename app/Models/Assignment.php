<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Assignment extends Model
{
    use HasFactory, HasTranslations;



    protected $fillable = [
        'course_id',
        'course_group_id',
        'course_curriculum_section_id',
        'title',
        'description',
        'attachment_path',
        'due_at',
        'max_grade',
        'is_published',
    ];

    public array $translatable = ['title'];

    protected function casts(): array
    {
        return [
            'attachment_path' => 'array',
            'due_at' => 'datetime',
            'max_grade' => 'int',
            'is_published' => 'bool',
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

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
