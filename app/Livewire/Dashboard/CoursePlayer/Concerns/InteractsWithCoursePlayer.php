<?php

namespace App\Livewire\Dashboard\CoursePlayer\Concerns;

use App\Enums\EnrollmentStatus;
use App\Models\Assignment;
use App\Models\ContentProgress;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Services\CourseProgressionService;
use App\Support\S3Url;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

trait InteractsWithCoursePlayer
{
    protected function itemIcon(string $type): string
    {
        return match ($type) {
            'lesson' => '<path d="M6.75 3.5A2.75 2.75 0 0 0 4 6.25v11.5a2.75 2.75 0 0 0 2.75 2.75h10.5A2.75 2.75 0 0 0 20 17.75V9.5607a2.75 2.75 0 0 0-.8055-1.9445l-2.8101-2.8101A2.75 2.75 0 0 0 14.4393 4H6.75Z"></path><path opacity="0.4" d="M8 11.25a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 8 11.25Zm0 3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5A.75.75 0 0 1 8 14.25Z"></path>',
            'session' => '<path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 10.25a.75.75 0 0 1-.3416.6324l-3 2A.75.75 0 1 1 8.825 13.6176L11.5 11.8343V7.75a.75.75 0 0 1 1.5 0v4.5Z"></path>',
            'quiz' => '<path d="M5.75 4A1.75 1.75 0 0 0 4 5.75v12.5C4 19.2165 4.7835 20 5.75 20h12.5c.9665 0 1.75-.7835 1.75-1.75V8.06066a1.75 1.75 0 0 0-.5126-1.23744l-2.3106-2.3106A1.75 1.75 0 0 0 15.9393 4H5.75Z"></path><path opacity="0.4" d="M8 12.25a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 8 12.25Zm0 3a.75.75 0 0 1 .75-.75h4a.75.75 0 0 1 0 1.5h-4A.75.75 0 0 1 8 15.25Z"></path>',
            'attachment' => '<path d="M7.75 3.5A2.75 2.75 0 0 0 5 6.25v11.5a2.75 2.75 0 0 0 2.75 2.75h8.5A2.75 2.75 0 0 0 19 17.75V8.8107a2.75 2.75 0 0 0-.8055-1.9445l-2.0607-2.0607A2.75 2.75 0 0 0 14.1893 4H7.75Z"></path><path opacity="0.4" d="M9 12.25a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5A.75.75 0 0 1 9 12.25Zm0 3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5A.75.75 0 0 1 9 15.25Z"></path>',
            'assignment' => '<path d="M9 12.75h6M9 15.75h6M9 9.75h3"></path><path opacity="0.4" d="M7.75 3.5A2.75 2.75 0 0 0 5 6.25v11.5a2.75 2.75 0 0 0 2.75 2.75h8.5A2.75 2.75 0 0 0 19 17.75V8.8107a2.75 2.75 0 0 0-.8055-1.9445l-2.0607-2.0607A2.75 2.75 0 0 0 14.1893 4H7.75Z"></path>',
            default => '<path d="M4 6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75v10.5A2.75 2.75 0 0 1 17.25 20H6.75A2.75 2.75 0 0 1 4 17.25V6.75Z"></path>',
        };
    }

    protected function resolveEnrollment(string $courseSlug, int $groupId): Enrollment
    {
        return Enrollment::query()
            ->where('user_id', auth()->id())
            ->where('course_group_id', $groupId)
            ->whereIn('status', [EnrollmentStatus::Active, EnrollmentStatus::PendingCertificate])
            ->whereHas('course', function ($query) use ($courseSlug) {
                $query->where('slug', $courseSlug);
            })
            ->with([
                'course.curriculumSections.attachments',
                'course.generalAttachments',
                'course.lessons.curriculumSection',
                'course.enrollments',
                'course.quizzes.curriculumSection',
                'courseGroup.curriculumSections.attachments',
                'courseGroup.sessions.curriculumSection',
                'course.assignments',
                'courseGroup.assignments',
            ])
            ->firstOrFail();
    }

    /**
     * Returns the sections the student should see: group-specific when they exist,
     * otherwise course-level sections (null course_group_id, legacy data).
     *
     * @return Collection<int, CourseCurriculumSection>
     */
    protected function effectiveSections(Enrollment $enrollment): Collection
    {
        $groupSections = $enrollment->courseGroup?->curriculumSections ?? collect();

        return $groupSections->isNotEmpty()
            ? $groupSections
            : $enrollment->course->curriculumSections->whereNull('course_group_id')->values();
    }

    protected function resolveDay(Enrollment $enrollment, int $dayId): CourseCurriculumSection
    {
        $day = $this->effectiveSections($enrollment)->firstWhere('id', $dayId);

        if (! $day) {
            throw (new ModelNotFoundException)->setModel(CourseCurriculumSection::class, [$dayId]);
        }

        return $day;
    }

    protected function resolveLesson(Enrollment $enrollment, int $lessonId): Lesson
    {
        $lesson = $enrollment->course->lessons->firstWhere('id', $lessonId);

        if (! $lesson) {
            throw (new ModelNotFoundException)->setModel(Lesson::class, [$lessonId]);
        }

        return $lesson;
    }

    protected function resolveSession(Enrollment $enrollment, int $sessionId): CourseGroupSession
    {
        $session = $enrollment->courseGroup->sessions->firstWhere('id', $sessionId);

        if (! $session) {
            throw (new ModelNotFoundException)->setModel(CourseGroupSession::class, [$sessionId]);
        }

        return $session;
    }

    protected function resolveQuiz(Enrollment $enrollment, int $quizId): Quiz
    {
        $quiz = $enrollment->course->quizzes->firstWhere('id', $quizId);

        if (! $quiz) {
            throw (new ModelNotFoundException)->setModel(Quiz::class, [$quizId]);
        }

        return $quiz;
    }

    protected function resolveAssignment(Enrollment $enrollment, int $assignmentId): Assignment
    {
        $assignment = $enrollment->course->assignments->firstWhere('id', $assignmentId);

        if (! $assignment || ! $assignment->is_published) {
            throw (new ModelNotFoundException)->setModel(Assignment::class, [$assignmentId]);
        }

        if ($assignment->course_group_id !== null && $assignment->course_group_id !== $enrollment->course_group_id) {
            throw (new ModelNotFoundException)->setModel(Assignment::class, [$assignmentId]);
        }

        return $assignment;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCourseMeta(Enrollment $enrollment): array
    {
        $course = $enrollment->course;
        $lessonsCount = $course->lessons->count();
        $sessionsCount = $enrollment->courseGroup->sessions->count();
        $attachmentsCount = $this->effectiveSections($enrollment)
            ->flatMap(fn ($section) => $section->attachments)
            ->count();
        $quizzesCount = $course->quizzes->count();
        $totalContent = $lessonsCount + $sessionsCount + $attachmentsCount + $quizzesCount;
        $completedContent = ContentProgress::query()
            ->where('user_id', auth()->id())
            ->where('course_id', $course->id)
            ->count();
        $completionPercent = $totalContent > 0 ? (int) round(($completedContent / $totalContent) * 100) : 0;

        return [
            'id' => $course->id,
            'title' => $course->getTranslation('title', app()->getLocale()),
            'group' => $enrollment->courseGroup->getTranslation('title', app()->getLocale()),
            'group_id' => $enrollment->courseGroup->id,
            'slug' => $course->slug,
            'image_url' => $course->image_url,
            'stats' => [
                'learners_count' => $course->enrollments->count(),
                'completion_percent' => max(0, min(100, $completionPercent)),
                'completed_content' => $completedContent,
                'total_content' => $totalContent,
                'sections_count' => $this->effectiveSections($enrollment)->count(),
                'sessions_count' => $sessionsCount,
                'lessons_count' => $lessonsCount,
            ],
        ];
    }

    protected function markContentViewed(string $type, int $id, int $courseId): void
    {
        $typeMap = [
            'lesson' => Lesson::class,
            'session' => CourseGroupSession::class,
            'attachment' => CourseAttachment::class,
            'quiz' => Quiz::class,
        ];

        $contentClass = $typeMap[$type] ?? null;

        if (! $contentClass || ! auth()->check()) {
            return;
        }

        $now = now();

        ContentProgress::query()->upsert([
            [
                'user_id' => auth()->id(),
                'course_id' => $courseId,
                'contentable_type' => $contentClass,
                'contentable_id' => $id,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['user_id', 'contentable_type', 'contentable_id'], ['course_id', 'updated_at']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildMainNavLinks(Enrollment $enrollment, ?CourseProgressionService $progression = null): array
    {
        return array_merge([
            [
                'key' => 'course-overview',
                'label' => __('web.course_player.overview'),
                'route' => route('dashboard.course-player', [$enrollment->course->slug, $enrollment->courseGroup->id]),
                'icon' => '<path d="M3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12Z"></path><path opacity="0.4" d="M12 7.5a.75.75 0 0 1 .75.75v3h2.25a.75.75 0 0 1 0 1.5H12a.75.75 0 0 1-.75-.75V8.25A.75.75 0 0 1 12 7.5Z"></path>',
                'is_active' => request()->routeIs('dashboard.course-player'),
                'is_delete' => false,
                'open_in_new_tab' => false,
                'is_disabled' => false,
            ],
            [
                'key' => 'attendance',
                'label' => __('web.course_player.my_attendance'),
                'route' => route('dashboard.course-player.attendance', [$enrollment->course->slug, $enrollment->courseGroup->id]),
                'icon' => '<path d="M7.75 3.5A2.75 2.75 0 0 0 5 6.25v11.5A2.75 2.75 0 0 0 7.75 20.5h8.5A2.75 2.75 0 0 0 19 17.75V6.25A2.75 2.75 0 0 0 16.25 3.5h-8.5Z"></path><path opacity="0.4" d="M9 12.75l2 2 4-4" stroke="#fff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"></path>',
                'is_active' => request()->routeIs('dashboard.course-player.attendance'),
                'is_delete' => false,
                'open_in_new_tab' => false,
                'is_disabled' => false,
            ],
        ], $this->buildAllDayNavLinks($enrollment, $progression));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    protected function buildDayNavLinks(Enrollment $enrollment, CourseCurriculumSection $day, Collection $items, ?CourseProgressionService $progression = null): array
    {
        return $this->buildMainNavLinks($enrollment, $progression);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildAllDayNavLinks(Enrollment $enrollment, ?CourseProgressionService $progression = null): array
    {
        $progression ??= app(CourseProgressionService::class);

        return $this->effectiveSections($enrollment)
            ->sortBy('sort_order')
            ->values()
            ->map(function (CourseCurriculumSection $day) use ($enrollment, $progression): array {
                $items = $this->buildDayItemsCollection($enrollment, $day, $progression);
                $isCurrentDay = request()->routeIs('dashboard.course-player.day') && (int) request()->route('day') === $day->id;
                $hasActiveChild = $items->contains(fn (array $item): bool => (bool) $item['is_active']);
                $state = $progression->dayState($enrollment, $day);
                $isLocked = $state === 'locked';

                return [
                    'key' => 'day-'.$day->id,
                    'label' => $day->getTranslation('title', app()->getLocale()),
                    'route' => $isLocked ? '#' : route('dashboard.course-player.day', [$enrollment->course->slug, $enrollment->courseGroup->id, $day->id]),
                    'icon' => '<path d="M4 6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75v10.5A2.75 2.75 0 0 1 17.25 20H6.75A2.75 2.75 0 0 1 4 17.25V6.75Z"></path><path opacity="0.4" d="M8 8.75h8M8 12h8M8 15.25h5"></path>',
                    'is_active' => $isCurrentDay || $hasActiveChild,
                    'is_delete' => false,
                    'open_in_new_tab' => false,
                    'has_children' => true,
                    'is_disabled' => $isLocked,
                    'state' => $state,
                    'children' => $items->map(function (array $item): array {
                        return [
                            'key' => $item['key'],
                            'label' => $item['title'],
                            'route' => $item['route'],
                            'icon' => $this->itemIcon($item['type']),
                            'is_active' => $item['is_active'],
                            'open_in_new_tab' => $item['open_in_new_tab'] ?? false,
                            'is_disabled' => $item['is_locked'] ?? false,
                            'state' => $item['state'] ?? 'available',
                        ];
                    })->all(),
                ];
            })
            ->all();
    }

    protected function attachmentUrl(string|array|null $path): ?string
    {
        if (is_array($path)) {
            $path = $path[0] ?? null;
        }

        if (! filled($path)) {
            return null;
        }

        return S3Url::temporary($path);
    }

    /**
     * @return array<int, array{url: string, name: string}>
     */
    protected function attachmentFiles(CourseAttachment $attachment): array
    {
        $paths = is_array($attachment->file_path)
            ? $attachment->file_path
            : array_filter([$attachment->file_path]);

        return collect($paths)
            ->filter()
            ->map(fn (string $path): array => [
                'url' => S3Url::temporary($path),
                'name' => basename($path),
            ])
            ->values()
            ->all();
    }

    /**
     * Course-wide attachments shown to every enrolled group, outside the day sections.
     *
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildGeneralAttachmentItems(Enrollment $enrollment): Collection
    {
        return $enrollment->course->generalAttachments
            ->map(function (CourseAttachment $attachment): array {
                $files = $this->attachmentFiles($attachment);

                return [
                    'id' => $attachment->id,
                    'key' => 'attachment-'.$attachment->id,
                    'title' => $attachment->getTranslation('title', app()->getLocale()),
                    'meta' => __('web.course_player.attachments'),
                    'type' => 'attachment',
                    'route' => $files[0]['url'] ?? '#',
                    'files' => $files,
                    'icon' => $this->itemIcon('attachment'),
                    'is_active' => false,
                    'open_in_new_tab' => true,
                    'state' => 'available',
                    'is_locked' => false,
                    'is_completed' => false,
                ];
            })
            ->values();
    }

    /**
     * @return array<int, array{url: string, name: string}>
     */
    protected function assignmentFiles(?array $paths): array
    {
        return collect($paths)
            ->filter()
            ->map(fn (string $path): array => [
                'url' => S3Url::temporary($path),
                'name' => basename($path),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function buildDayItemsCollection(Enrollment $enrollment, CourseCurriculumSection $day, ?CourseProgressionService $progression = null): Collection
    {
        $progression ??= app(CourseProgressionService::class);
        $courseSlug = $enrollment->course->slug;

        $lessonItems = $enrollment->course->lessons
            ->where('course_curriculum_section_id', $day->id)
            ->sortBy('sort_order')
            ->map(function (Lesson $lesson) use ($courseSlug, $enrollment, $progression): array {
                $state = $progression->itemState($enrollment, 'lesson', $lesson);
                $isLocked = $state === 'locked';

                return [
                    'id' => $lesson->id,
                    'key' => 'lesson-'.$lesson->id,
                    'title' => $lesson->getTranslation('title', app()->getLocale()),
                    'meta' => __('web.course_player.lesson_types.'.$lesson->type->value),
                    'type' => 'lesson',
                    'route' => $isLocked ? '#' : route('dashboard.course-player.lesson', [$courseSlug, $enrollment->courseGroup->id, $lesson->id]),
                    'icon' => $this->itemIcon('lesson'),
                    'is_active' => request()->routeIs('dashboard.course-player.lesson') && (int) request()->route('lesson') === $lesson->id,
                    'state' => $state,
                    'is_locked' => $isLocked,
                    'is_completed' => $state === 'completed',
                ];
            });

        $sessionItems = $enrollment->courseGroup->sessions
            ->where('course_curriculum_section_id', $day->id)
            ->sortBy('start_at')
            ->map(function (CourseGroupSession $session) use ($courseSlug, $enrollment, $progression): array {
                $state = $progression->itemState($enrollment, 'session', $session);
                $isLocked = $state === 'locked';

                return [
                    'id' => $session->id,
                    'key' => 'session-'.$session->id,
                    'title' => $session->title,
                    'meta' => optional($session->start_at)->format('Y-m-d H:i'),
                    'type' => 'session',
                    'route' => $isLocked ? '#' : route('dashboard.course-player.session', [$courseSlug, $enrollment->courseGroup->id, $session->id]),
                    'icon' => $this->itemIcon('session'),
                    'is_active' => request()->routeIs('dashboard.course-player.session') && (int) request()->route('session') === $session->id,
                    'state' => $state,
                    'is_locked' => $isLocked,
                    'is_completed' => $state === 'completed',
                ];
            });

        $attachmentItems = $day->attachments
            ->sortBy('id')
            ->map(function (CourseAttachment $attachment) use ($enrollment, $progression): array {
                $state = $progression->itemState($enrollment, 'attachment', $attachment);
                $isLocked = $state === 'locked';
                $files = $isLocked ? [] : $this->attachmentFiles($attachment);

                return [
                    'id' => $attachment->id,
                    'key' => 'attachment-'.$attachment->id,
                    'title' => $attachment->getTranslation('title', app()->getLocale()),
                    'meta' => __('web.course_player.attachments'),
                    'type' => 'attachment',
                    'route' => $files[0]['url'] ?? '#',
                    'files' => $files,
                    'icon' => $this->itemIcon('attachment'),
                    'is_active' => false,
                    'open_in_new_tab' => ! $isLocked,
                    'state' => $state,
                    'is_locked' => $isLocked,
                    'is_completed' => $state === 'completed',
                ];
            });

        $assignmentItems = $enrollment->course->assignments
            ->filter(fn (Assignment $assignment): bool => $assignment->is_published
                && $assignment->course_curriculum_section_id === $day->id
                && ($assignment->course_group_id === null || $assignment->course_group_id === $enrollment->course_group_id))
            ->values()
            ->map(function (Assignment $assignment) use ($courseSlug, $enrollment, $progression): array {
                $state = $progression->itemState($enrollment, 'assignment', $assignment);
                $isLocked = $state === 'locked';

                return [
                    'id' => $assignment->id,
                    'key' => 'assignment-'.$assignment->id,
                    'title' => $assignment->getTranslation('title', app()->getLocale()),
                    'meta' => __('web.course_player.assignments'),
                    'type' => 'assignment',
                    'route' => $isLocked ? '#' : route('dashboard.course-player.assignment', [$courseSlug, $enrollment->courseGroup->id, $assignment->id]),
                    'icon' => $this->itemIcon('assignment'),
                    'is_active' => request()->routeIs('dashboard.course-player.assignment') && (int) request()->route('assignment') === $assignment->id,
                    'state' => $state,
                    'is_locked' => $isLocked,
                    'is_completed' => $state === 'completed',
                ];
            });

        $quizItems = $enrollment->course->quizzes
            ->filter(fn (Quiz $quiz): bool => $quiz->course_curriculum_section_id === $day->id || ($quiz->course_curriculum_section_id === null && (int) $day->sort_order === 1))
            ->values()
            ->map(function (Quiz $quiz) use ($courseSlug, $enrollment, $progression): array {
                $state = $progression->itemState($enrollment, 'quiz', $quiz);
                $isLocked = $state === 'locked';

                return [
                    'id' => $quiz->id,
                    'key' => 'quiz-'.$quiz->id,
                    'title' => $quiz->title,
                    'meta' => __('web.quizzes.tab'),
                    'type' => 'quiz',
                    'route' => $isLocked ? '#' : route('dashboard.course-player.quiz', [$courseSlug, $enrollment->courseGroup->id, $quiz->id]),
                    'icon' => $this->itemIcon('quiz'),
                    'is_active' => request()->routeIs('dashboard.course-player.quiz') && (int) request()->route('quiz') === $quiz->id,
                    'state' => $state,
                    'is_locked' => $isLocked,
                    'is_completed' => $state === 'completed',
                ];
            });

        return $lessonItems
            ->concat($sessionItems)
            ->concat($attachmentItems)
            ->concat($assignmentItems)
            ->concat($quizItems)
            ->values();
    }

    protected function redirectToFirstAccessibleDay(
        Enrollment $enrollment,
        string $message,
        ?CourseProgressionService $progression = null,
    ) {
        $progression ??= app(CourseProgressionService::class);
        session()->flash('status', $message);

        $day = $progression->firstAccessibleDay($enrollment);

        if ($day) {
            return redirect()->route('dashboard.course-player.day', [$enrollment->course->slug, $enrollment->courseGroup->id, $day->id]);
        }

        return redirect()->route('dashboard.course-player', [$enrollment->course->slug, $enrollment->courseGroup->id]);
    }
}
