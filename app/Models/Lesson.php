<?php

namespace App\Models;

use App\Enums\LessonType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Translatable\HasTranslations;

class Lesson extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'course_id',
        'course_curriculum_section_id',
        'title',
        'content',
        'type',
        'pdf_path',
        'video_url',
        'is_preview',
        'sort_order',
    ];

    public array $translatable = ['title', 'content'];

    protected function casts(): array
    {
        return [
            'type' => LessonType::class,
            'is_preview' => 'bool',
            'sort_order' => 'int',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function curriculumSection(): BelongsTo
    {
        return $this->belongsTo(CourseCurriculumSection::class, 'course_curriculum_section_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
