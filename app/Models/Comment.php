<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;

    protected $fillable = [
        'commentable_id',
        'commentable_type',
        'user_id',
        'content',
        'approved',
        'hidden',
    ];

    protected function casts(): array
    {
        return [
            'approved' => 'bool',
            'hidden' => 'bool',
        ];
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommentReply::class)->whereNull('parent_reply_id');
    }

    public function allReplies(): HasMany
    {
        return $this->hasMany(CommentReply::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('approved', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('approved', true)->where('hidden', false);
    }

    public static function applyAdminContextEagerLoading(Builder $query): Builder
    {
        return $query->with([
            'user',
            'allReplies.user',
            'allReplies.parentReply.user',
            'commentable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Course::class => [],
                    Lesson::class => ['course', 'curriculumSection'],
                    CourseAttachment::class => ['course', 'curriculumSection'],
                    CourseGroupSession::class => ['courseGroup.course', 'curriculumSection'],
                    Page::class => [],
                ]);
            },
        ]);
    }

    /**
     * @return array{course_title:string,group_title:string,related_type:string,related_title:string}
     */
    public function adminContext(): array
    {
        $commentable = $this->commentable;

        return match (true) {
            $commentable instanceof Course => [
                'course_title' => $this->resolveTitle($commentable),
                'group_title' => '-',
                'related_type' => __('admin.labels.course'),
                'related_title' => $this->resolveTitle($commentable),
            ],
            $commentable instanceof Lesson => [
                'course_title' => $this->resolveTitle($commentable->course),
                'group_title' => $this->resolveTitle($commentable->curriculumSection),
                'related_type' => __('admin.labels.lesson'),
                'related_title' => $this->resolveTitle($commentable),
            ],
            $commentable instanceof CourseAttachment => [
                'course_title' => $this->resolveTitle($commentable->course),
                'group_title' => $this->resolveTitle($commentable->curriculumSection),
                'related_type' => __('admin.labels.attachment'),
                'related_title' => $this->resolveTitle($commentable),
            ],
            $commentable instanceof CourseGroupSession => [
                'course_title' => $this->resolveTitle($commentable->courseGroup?->course),
                'group_title' => $this->resolveTitle($commentable->courseGroup),
                'related_type' => __('admin.labels.session'),
                'related_title' => $this->resolveTitle($commentable),
            ],
            $commentable instanceof Page => [
                'course_title' => '-',
                'group_title' => '-',
                'related_type' => __('admin.labels.page'),
                'related_title' => $this->resolveTitle($commentable),
            ],
            default => [
                'course_title' => '-',
                'group_title' => '-',
                'related_type' => class_basename((string) $this->commentable_type),
                'related_title' => '-',
            ],
        };
    }

    public static function applyAdminCourseSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $builder) use ($search): void {
            $builder->whereHasMorph('commentable', [Course::class], function (Builder $morphQuery) use ($search): void {
                $morphQuery
                    ->where('title->en', 'like', "%{$search}%")
                    ->orWhere('title->ar', 'like', "%{$search}%");
            })->orWhereHasMorph('commentable', [Lesson::class, CourseAttachment::class], function (Builder $morphQuery) use ($search): void {
                $morphQuery->whereHas('course', function (Builder $courseQuery) use ($search): void {
                    $courseQuery
                        ->where('title->en', 'like', "%{$search}%")
                        ->orWhere('title->ar', 'like', "%{$search}%");
                });
            })->orWhereHasMorph('commentable', [CourseGroupSession::class], function (Builder $morphQuery) use ($search): void {
                $morphQuery->whereHas('courseGroup.course', function (Builder $courseQuery) use ($search): void {
                    $courseQuery
                        ->where('title->en', 'like', "%{$search}%")
                        ->orWhere('title->ar', 'like', "%{$search}%");
                });
            });
        });
    }

    public static function applyAdminGroupSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $builder) use ($search): void {
            $builder->whereHasMorph('commentable', [Lesson::class, CourseAttachment::class], function (Builder $morphQuery) use ($search): void {
                $morphQuery->whereHas('curriculumSection', function (Builder $sectionQuery) use ($search): void {
                    $sectionQuery
                        ->where('title->en', 'like', "%{$search}%")
                        ->orWhere('title->ar', 'like', "%{$search}%");
                });
            })->orWhereHasMorph('commentable', [CourseGroupSession::class], function (Builder $morphQuery) use ($search): void {
                $morphQuery->whereHas('courseGroup', function (Builder $groupQuery) use ($search): void {
                    $groupQuery
                        ->where('title->en', 'like', "%{$search}%")
                        ->orWhere('title->ar', 'like', "%{$search}%");
                });
            });
        });
    }

    public static function applyAdminRelatedTitleSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $builder) use ($search): void {
            // Translatable (JSON) title columns
            $builder->whereHasMorph('commentable', [Course::class, Lesson::class, CourseAttachment::class, Page::class], function (Builder $morphQuery) use ($search): void {
                $morphQuery
                    ->where('title->en', 'like', "%{$search}%")
                    ->orWhere('title->ar', 'like', "%{$search}%");
            });

            // Plain-text title column — json_extract must not be applied here
            $builder->orWhereHasMorph('commentable', [CourseGroupSession::class], function (Builder $morphQuery) use ($search): void {
                $morphQuery->where('title', 'like', "%{$search}%");
            });
        });
    }

    private function resolveTitle(mixed $record): string
    {
        if (! $record) {
            return '-';
        }

        if (method_exists($record, 'getTranslation')) {
            return (string) ($record->getTranslation('title', app()->getLocale()) ?: '-');
        }

        $title = $record->title ?? null;

        return filled($title) ? (string) $title : '-';
    }
}
