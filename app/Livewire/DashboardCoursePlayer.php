<?php

namespace App\Livewire;

use App\Models\ContentProgress;
use App\Models\CourseAttachment;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\SessionStatusService;
use App\Support\S3Url;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class DashboardCoursePlayer extends Component
{
    public string $course_slug;

    public ?int $activeQuizId = null;

    /**
     * @var array<int, int|string>
     */
    public array $quizAnswers = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $quizResults = [];

    public ?string $quizError = null;

    public function mount(string $course_slug): void
    {
        $this->course_slug = $course_slug;
    }

    public function markContentViewed(string $type, int $id): void
    {
        if (! auth()->check()) {
            return;
        }

        $enrollment = Enrollment::query()
            ->where('user_id', auth()->id())
            ->whereHas('course', function ($query) {
                $query->where('slug', $this->course_slug);
            })
            ->with('courseGroup')
            ->first();

        if (! $enrollment) {
            return;
        }

        $contentable = $this->resolveContentable($type, $id);
        if (! $contentable) {
            return;
        }

        $courseId = $this->resolveContentCourseId($contentable);
        if (! $courseId || $courseId !== $enrollment->course_id) {
            return;
        }

        $now = now();
        ContentProgress::query()->upsert([
            [
                'user_id' => auth()->id(),
                'course_id' => $courseId,
                'contentable_type' => $contentable::class,
                'contentable_id' => $contentable->id,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['user_id', 'contentable_type', 'contentable_id'], ['course_id', 'updated_at']);

        $this->dispatch('content-viewed');
        $this->skipRender();
    }

    public function startQuiz(int $quizId): void
    {
        $this->activeQuizId = $quizId;
        $this->quizAnswers = [];
        $this->quizError = null;
    }

    public function submitQuiz(int $quizId): void
    {
        $this->quizError = null;

        $enrollment = Enrollment::query()
            ->where('user_id', auth()->id())
            ->whereHas('course', function ($query) {
                $query->where('slug', $this->course_slug);
            })
            ->first();

        if (! $enrollment) {
            return;
        }

        $quiz = Quiz::query()
            ->where('course_id', $enrollment->course_id)
            ->whereHas('questions.options')
            ->with(['questions' => function ($query) {
                $query->whereHas('options')->with('options');
            }])
            ->find($quizId);

        if (! $quiz) {
            $this->quizError = __('web.quizzes.not_available');

            return;
        }

        if (QuizAttempt::query()->where('quiz_id', $quiz->id)->where('user_id', auth()->id())->exists()) {
            $this->quizError = __('web.quizzes.attempted_message');

            return;
        }

        $questions = $this->validQuizQuestions($quiz->questions);
        if ($questions->isEmpty()) {
            $this->quizError = __('web.quizzes.not_available');

            return;
        }

        $rules = [];
        foreach ($questions as $question) {
            $optionIds = $question->options->pluck('id')->all();
            $rules['quizAnswers.'.$question->id] = ['required', 'integer', Rule::in($optionIds)];
        }

        if ($rules === []) {
            $this->quizError = __('web.quizzes.not_available');

            return;
        }

        $this->validate($rules);

        $results = [];
        $score = 0;
        $totalPoints = 0;

        foreach ($questions as $question) {
            $totalPoints += $question->points;
            $selectedOptionId = (int) ($this->quizAnswers[$question->id] ?? 0);
            $selectedOption = $question->options->firstWhere('id', $selectedOptionId);
            $correctOption = $question->options->firstWhere('is_correct', true);
            $isCorrect = $correctOption && $selectedOption?->id === $correctOption->id;
            if ($isCorrect) {
                $score += $question->points;
            }

            $results[] = [
                'question' => $question->question,
                'selected' => $selectedOption?->label ?? __('web.quizzes.not_available'),
                'correct' => $correctOption?->label ?? __('web.quizzes.not_available'),
                'is_correct' => $isCorrect,
                'points' => $question->points,
            ];
        }

        QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => auth()->id(),
            'score' => $score,
            'answers' => $this->quizAnswers,
        ]);

        $this->quizResults[$quiz->id] = [
            'score' => $score,
            'total_points' => $totalPoints,
            'items' => $results,
        ];

        $this->dispatch('toast', message: __('web.quizzes.submitted'));
    }

    public function render(SessionStatusService $sessionStatus): View
    {
        $enrollment = Enrollment::query()
            ->where('user_id', auth()->id())
            ->whereHas('course', function ($query) {
                $query->where('slug', $this->course_slug);
            })
            ->with([
                'course.curriculumSections',
                'course.lessons.curriculumSection',
                'course.attachments.curriculumSection',
                'course.enrollments',
                'courseGroup.sessions.curriculumSection',
            ])
            ->firstOrFail();

        $lessons = $enrollment->course->lessons->sortBy('sort_order')->map(function ($lesson) {
            $resourceUrl = null;
            if (filled($lesson->video_url)) {
                $resourceUrl = $lesson->video_url;
            } elseif (filled($lesson->pdf_path)) {
                $resourceUrl = S3Url::temporary($lesson->pdf_path);
            }

            return [
                'id' => $lesson->id,
                'curriculum_section_id' => $lesson->course_curriculum_section_id,
                'sort_order' => $lesson->sort_order ?? 0,
                'title' => $lesson->getTranslation('title', app()->getLocale()),
                'type' => $lesson->type->value,
                'is_preview' => $lesson->is_preview,
                'resource_url' => $resourceUrl,
                'content' => filled($lesson->content) ? $lesson->content : __('web.course_player.no_content'),
                'anchor' => 'content-lesson-'.$lesson->id,
            ];
        })->values();

        $attachments = $enrollment->course->attachments->map(function ($attachment) {
            return [
                'id' => $attachment->id,
                'curriculum_section_id' => $attachment->course_curriculum_section_id,
                'title' => $attachment->getTranslation('title', app()->getLocale()),
                'visibility' => $attachment->visibility->value,
                'url' => filled($attachment->file_path)
                    ? S3Url::temporary($attachment->file_path)
                    : null,
                'anchor' => 'content-attachment-'.$attachment->id,
            ];
        })->values();

        $sessions = $enrollment->courseGroup->sessions->map(function ($session) use ($sessionStatus) {
            $attachments = collect($session->attachments ?? [])
                ->filter(fn ($path) => is_string($path) && $path !== '')
                ->map(function (string $path): array {
                    return [
                        'name' => basename($path),
                        'url' => S3Url::temporary($path),
                    ];
                })
                ->filter(fn (array $attachment) => filled($attachment['url']))
                ->values()
                ->all();

            return [
                'id' => $session->id,
                'curriculum_section_id' => $session->course_curriculum_section_id,
                'title' => $session->title,
                'start_at' => $session->start_at?->format('Y-m-d H:i'),
                'status' => $sessionStatus->status($session)->value,
                'join_url' => $session->join_url,
                'recording_url' => $session->recording_url,
                'content' => filled($session->content) ? $session->content : null,
                'attachments' => $attachments,
                'anchor' => 'content-session-'.$session->id,
            ];
        })->values();

        $curriculumSections = $this->buildCurriculumSections(
            $enrollment->course->curriculumSections,
            $lessons,
            $attachments,
            $sessions
        );
        $totalContent = $lessons->count() + $attachments->count() + $sessions->count();

        $completedContent = ContentProgress::query()
            ->where('user_id', auth()->id())
            ->where('course_id', $enrollment->course->id)
            ->count();
        $completionPercent = $totalContent > 0 ? (int) round(($completedContent / $totalContent) * 100) : 0;
        $completionPercent = max(0, min(100, $completionPercent));
        $navLinks = $this->buildCurriculumNavLinks($curriculumSections);

        $quizzesQuery = Quiz::query()
            ->where('course_id', $enrollment->course->id)
            ->whereHas('questions.options')
            ->withCount(['questions as questions_count' => function ($query) {
                $query->whereHas('options');
            }]);
        $quizzesCollection = $quizzesQuery->get();
        $quizAttempts = QuizAttempt::query()
            ->where('user_id', auth()->id())
            ->whereIn('quiz_id', $quizzesCollection->pluck('id')->filter()->values())
            ->latest('id')
            ->get()
            ->groupBy('quiz_id')
            ->map(fn ($attempts) => $attempts->first());

        $quizzes = $quizzesCollection->map(function (Quiz $quiz) use ($quizAttempts) {
            $attempt = $quizAttempts[$quiz->id] ?? null;
            $score = $attempt?->score;
            $passingScore = $quiz->passing_score;
            $status = $score === null
                ? 'not_attempted'
                : ($passingScore !== null && $score >= $passingScore ? 'passed' : 'failed');

            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'questions_count' => $quiz->questions_count ?? 0,
                'score' => $score,
                'passing_score' => $passingScore,
                'status' => $status,
                'has_attempt' => $attempt !== null,
            ];
        });

        $activeQuiz = null;
        $activeQuizAttempt = null;
        $activeQuizResults = null;
        if ($this->activeQuizId) {
            $activeQuiz = Quiz::query()
                ->where('course_id', $enrollment->course->id)
                ->whereHas('questions.options')
                ->with(['questions' => function ($query) {
                    $query->whereHas('options')->with('options');
                }])
                ->find($this->activeQuizId);
            $activeQuizAttempt = $activeQuiz
                ? QuizAttempt::query()
                    ->where('quiz_id', $activeQuiz->id)
                    ->where('user_id', auth()->id())
                    ->latest('id')
                    ->first()
                : null;
            if ($activeQuiz) {
                $activeQuizResults = $this->quizResults[$activeQuiz->id]
                    ?? $this->buildQuizReview($activeQuiz, $activeQuizAttempt);
            }
        }

        return view('livewire.dashboard-course-player', [
            'course' => [
                'id' => $enrollment->course->id,
                'title' => $enrollment->course->getTranslation('title', app()->getLocale()),
                'group' => $enrollment->courseGroup->getTranslation('title', app()->getLocale()),
                'slug' => $enrollment->course->slug,
                'image_url' => $enrollment->course->image_url,
                'stats' => [
                    'learners_count' => $enrollment->course->enrollments->count(),
                    'completed_content' => $completedContent,
                    'completion_percent' => $completionPercent,
                    'total_content' => $totalContent,
                    'sections_count' => $curriculumSections->count(),
                    'sessions_count' => $sessions->count(),
                    'lessons_count' => $lessons->count(),
                ],
            ],
            'curriculumSections' => $curriculumSections,
            'navLinks' => $navLinks,
            'quizzes' => $quizzes,
            'activeQuiz' => $activeQuiz,
            'activeQuizResults' => $activeQuizResults,
            'activeQuizAttempt' => $activeQuizAttempt,
        ])->layout('layouts.app');
    }

    private function resolveContentable(string $type, int $id): Lesson|CourseAttachment|CourseGroupSession|null
    {
        return match ($type) {
            'lesson' => Lesson::query()->find($id),
            'attachment' => CourseAttachment::query()->find($id),
            'session' => CourseGroupSession::query()->find($id),
            default => null,
        };
    }

    private function resolveContentCourseId(Lesson|CourseAttachment|CourseGroupSession $contentable): ?int
    {
        return match (true) {
            $contentable instanceof Lesson => $contentable->course_id,
            $contentable instanceof CourseAttachment => $contentable->course_id,
            $contentable instanceof CourseGroupSession => $contentable->courseGroup?->course_id,
            default => null,
        };
    }

    /**
     * @return array{score:int, total_points:int, items:array<int, array<string, mixed>>}|null
     */
    private function buildQuizReview(Quiz $quiz, ?QuizAttempt $attempt): ?array
    {
        if (! $attempt || ! is_array($attempt->answers)) {
            return null;
        }

        $score = 0;
        $totalPoints = 0;
        $items = [];
        foreach ($this->validQuizQuestions($quiz->questions) as $question) {
            $totalPoints += $question->points;
            $selectedOptionId = (int) ($attempt->answers[$question->id] ?? 0);
            $selectedOption = $question->options->firstWhere('id', $selectedOptionId);
            $correctOption = $question->options->firstWhere('is_correct', true);
            $isCorrect = $correctOption && $selectedOption?->id === $correctOption->id;
            if ($isCorrect) {
                $score += $question->points;
            }

            $items[] = [
                'question' => $question->question,
                'selected' => $selectedOption?->label ?? __('web.quizzes.not_available'),
                'correct' => $correctOption?->label ?? __('web.quizzes.not_available'),
                'is_correct' => $isCorrect,
                'points' => $question->points,
            ];
        }

        return [
            'score' => $score,
            'total_points' => $totalPoints,
            'items' => $items,
        ];
    }

    /**
     * @param  Collection<int, \App\Models\QuizQuestion>  $questions
     * @return Collection<int, \App\Models\QuizQuestion>
     */
    private function validQuizQuestions(Collection $questions): Collection
    {
        return $questions->filter(function ($question) {
            return $question->options->isNotEmpty();
        })->values();
    }

    /**
     * @param  Collection<int, mixed>  $sections
     * @param  Collection<int, array<string, mixed>>  $lessons
     * @param  Collection<int, array<string, mixed>>  $attachments
     * @param  Collection<int, array<string, mixed>>  $sessions
     * @return Collection<int, array<string, mixed>>
     */
    private function buildCurriculumSections(
        Collection $sections,
        Collection $lessons,
        Collection $attachments,
        Collection $sessions
    ): Collection {
        $structuredSections = $sections
            ->sortBy('sort_order')
            ->map(function ($section) use ($lessons, $attachments, $sessions): array {
                return [
                    'id' => $section->id,
                    'key' => 'section-'.$section->id,
                    'title' => $section->getTranslation('title', app()->getLocale()),
                    'description' => filled($section->description) ? $section->getTranslation('description', app()->getLocale()) : null,
                    'items' => $this->buildCurriculumItemsForSection($section->id, $lessons, $attachments, $sessions),
                ];
            })
            ->filter(fn (array $section): bool => filled($section['title']) || $section['items'] !== [])
            ->values();

        $ungroupedItems = $this->buildCurriculumItemsForSection(null, $lessons, $attachments, $sessions);
        if ($ungroupedItems !== []) {
            $structuredSections->push([
                'id' => null,
                'key' => 'section-ungrouped',
                'title' => __('web.course_player.general_section'),
                'description' => null,
                'items' => $ungroupedItems,
            ]);
        }

        return $structuredSections->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lessons
     * @param  Collection<int, array<string, mixed>>  $attachments
     * @param  Collection<int, array<string, mixed>>  $sessions
     * @return array<int, array<string, mixed>>
     */
    private function buildCurriculumItemsForSection(
        ?int $sectionId,
        Collection $lessons,
        Collection $attachments,
        Collection $sessions
    ): array {
        $sectionLessons = $lessons
            ->filter(fn (array $lesson): bool => $lesson['curriculum_section_id'] === $sectionId)
            ->values()
            ->map(fn (array $lesson): array => array_merge($lesson, [
                'content_type' => 'lesson',
                'type_label' => __('web.course_player.lesson_types.'.$lesson['type']),
                'nav_label' => $lesson['title'],
                'order_key' => '2-'.str_pad((string) ($lesson['sort_order'] ?? 0), 5, '0', STR_PAD_LEFT).'-'.$lesson['id'],
            ]));

        $sectionSessions = $sessions
            ->filter(fn (array $session): bool => $session['curriculum_section_id'] === $sectionId)
            ->values()
            ->map(fn (array $session): array => array_merge($session, [
                'content_type' => 'session',
                'type_label' => __('web.course_player.sessions'),
                'nav_label' => $session['title'],
                'order_key' => '1-'.($session['start_at'] ?? '').'-'.$session['id'],
            ]));

        $sectionAttachments = $attachments
            ->filter(fn (array $attachment): bool => $attachment['curriculum_section_id'] === $sectionId)
            ->values()
            ->map(fn (array $attachment): array => array_merge($attachment, [
                'content_type' => 'attachment',
                'type_label' => __('web.course_player.attachments'),
                'nav_label' => $attachment['title'],
                'order_key' => '3-'.Str::lower($attachment['title']).'-'.$attachment['id'],
            ]));

        return $sectionSessions
            ->concat($sectionLessons)
            ->concat($sectionAttachments)
            ->sortBy('order_key')
            ->values()
            ->map(function (array $item): array {
                unset($item['order_key']);

                return $item;
            })
            ->all();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $sections
     * @return array<int, array<string, mixed>>
     */
    private function buildCurriculumNavLinks(Collection $sections): array
    {
        $links = [[
            'key' => 'course-overview',
            'label' => __('web.course_player.overview'),
            'route' => '#course-overview',
            'icon' => '<path d="M3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12Z"></path><path opacity="0.4" d="M12 7.5a.75.75 0 0 1 .75.75v3h2.25a.75.75 0 0 1 0 1.5H12a.75.75 0 0 1-.75-.75V8.25A.75.75 0 0 1 12 7.5Z"></path>',
            'is_active' => false,
            'is_delete' => false,
            'open_in_new_tab' => false,
            'has_children' => false,
            'children' => [],
        ]];

        $sectionLinks = $sections->map(function (array $section): array {
            return [
                'key' => $section['key'],
                'label' => $section['title'],
                'route' => '#'.$section['key'],
                'icon' => '<path d="M4 6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75v10.5A2.75 2.75 0 0 1 17.25 20H6.75A2.75 2.75 0 0 1 4 17.25V6.75Z"></path><path opacity="0.4" d="M8 8.75h8M8 12h8M8 15.25h5"></path>',
                'is_active' => false,
                'is_delete' => false,
                'open_in_new_tab' => false,
                'has_children' => count($section['items']) > 0,
                'children' => collect($section['items'])->map(function (array $item): array {
                    return [
                        'key' => $item['anchor'],
                        'label' => $item['nav_label'],
                        'route' => '#'.$item['anchor'],
                        'is_active' => false,
                        'open_in_new_tab' => false,
                    ];
                })->all(),
            ];
        })->all();

        $footerLinks = [
            [
                'key' => 'course-comments',
                'label' => __('web.comments.tab'),
                'route' => '#course-comments',
                'icon' => '<path d="M4 6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75v6.5A2.75 2.75 0 0 1 17.25 16H11l-4.5 4V16H6.75A2.75 2.75 0 0 1 4 13.25v-6.5Z"></path>',
                'is_active' => false,
                'is_delete' => false,
                'open_in_new_tab' => false,
                'has_children' => false,
                'children' => [],
            ],
            [
                'key' => 'course-quizzes',
                'label' => __('web.quizzes.tab'),
                'route' => '#course-quizzes',
                'icon' => '<path d="M5.75 4A1.75 1.75 0 0 0 4 5.75v12.5C4 19.2165 4.7835 20 5.75 20h12.5c.9665 0 1.75-.7835 1.75-1.75V8.06066a1.75 1.75 0 0 0-.5126-1.23744l-2.3106-2.3106A1.75 1.75 0 0 0 15.9393 4H5.75Z"></path><path opacity="0.4" d="M8 12.25a.75.75 0 0 1 .75-.75h6.5a.75.75 0 0 1 0 1.5h-6.5A.75.75 0 0 1 8 12.25Zm0 3a.75.75 0 0 1 .75-.75h4a.75.75 0 0 1 0 1.5h-4A.75.75 0 0 1 8 15.25Zm0-6a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5A.75.75 0 0 1 8 9.25Z"></path>',
                'is_active' => false,
                'is_delete' => false,
                'open_in_new_tab' => false,
                'has_children' => false,
                'children' => [],
            ],
        ];

        return array_merge($links, $sectionLinks, $footerLinks);
    }
}
