<?php

namespace App\Models;

use App\Enums\AttachmentVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class CourseAttachment extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'course_id',
        'course_curriculum_section_id',
        'title',
        'file_path',
        'visibility',
    ];

    public array $translatable = ['title'];

    /**
     * General attachments belong to the whole course, never to a single group's day.
     */
    protected static function booted(): void
    {
        static::saving(function (CourseAttachment $attachment): void {
            if ($attachment->visibility === AttachmentVisibility::General) {
                $attachment->course_curriculum_section_id = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'visibility' => AttachmentVisibility::class,
            'file_path' => 'array',
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
}
