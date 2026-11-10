<?php

namespace App\Livewire\Dashboard\CoursePlayer;

use App\Jobs\Nelc\SyncAttemptedJob;
use App\Livewire\Dashboard\CoursePlayer\Concerns\InteractsWithCoursePlayer;
use App\Models\QuizAttempt;
use App\Services\CourseProgressionService;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Quiz extends Component
{
    use InteractsWithCoursePlayer;

    public string $course_slug;

    public int $group;

    public int $quiz;

    /**
     * @var array<int, int|string>
     */
    public array $quizAnswers = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $quizResults = null;

    public bool $retaking = false;

    public ?string $quizError = null;

    public function mount(string $course_slug, int $group, int $quiz): void
    {
        $this->course_slug = $course_slug;
        $this->group = $group;
        $this->quiz = $quiz;

        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $quizRecord = $this->resolveQuiz($enrollment, $this->quiz);
        $progression = app(CourseProgressionService::class);

        if (! $progression->isQuizAccessible($enrollment, $quizRecord)) {
            session()->flash('status', __('web.course_player.locked_quiz_message'));
            $firstAccessibleDay = $progression->firstAccessibleDay($enrollment);

            if ($firstAccessibleDay) {
                $this->redirectRoute('dashboard.course-player.day', [$this->course_slug, $this->group, $firstAccessibleDay->id]);

                return;
            }

            $this->redirectRoute('dashboard.course-player', [$this->course_slug, $this->group]);
        }
    }

    public function submitQuiz(): void
    {
        $this->quizError = null;
        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $quiz = $this->resolveQuiz($enrollment, $this->quiz);
        $progression = app(CourseProgressionService::class);

        if (! $progression->isQuizAccessible($enrollment, $quiz)) {
            $this->quizError = __('web.course_player.locked_quiz_hint');

            return;
        }

        $attemptCount = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', auth()->id())
            ->count();

        if ($attemptCount > 0 && ! $quiz->allow_resubmit) {
            $this->quizError = __('web.quizzes.attempted_message');

            return;
        }

        if ($quiz->allow_resubmit && $quiz->max_attempts !== null && $attemptCount >= $quiz->max_attempts) {
            $this->quizError = __('web.quizzes.max_attempts_reached');

            return;
        }

        $quiz->load(['questions' => function ($query) {
            $query->whereHas('options')->with('options');
        }]);

        $questions = $this->validQuizQuestions($quiz->questions);
        if ($questions->isEmpty()) {
            $this->quizError = __('web.quizzes.not_available');

            return;
        }

        $rules = [];
        foreach ($questions as $question) {
            $rules['quizAnswers.'.$question->id] = ['required', 'integer', Rule::in($question->options->pluck('id')->all())];
        }

        $this->validate($rules);

        $score = 0;
        $totalPoints = 0;
        $items = [];

        foreach ($questions as $question) {
            $totalPoints += $question->points;
            $selectedOptionId = (int) ($this->quizAnswers[$question->id] ?? 0);
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
            ];
        }

        $attempt = QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => auth()->id(),
            'score' => $score,
            'answers' => $this->quizAnswers,
        ]);

        SyncAttemptedJob::dispatch($attempt->id);

        try {
            app(NotificationService::class)->notifyInstructorsQuizSubmitted($enrollment, $quiz, $score, $totalPoints);
        } catch (\Throwable) {
            // notification failure must not block quiz submission
        }

        $this->retaking = false;
        $this->quizResults = [
            'score' => $score,
            'total_points' => $totalPoints,
            'items' => $items,
        ];

        $this->markContentViewed('quiz', $quiz->id, $enrollment->course_id);

        $this->dispatch('gtm-event', name: 'quiz_complete', data: [
            'quiz_id' => (string) $quiz->id,
            'quiz_title' => $quiz->title,
            'course_id' => (string) $enrollment->course_id,
            'score' => $score,
            'total_points' => $totalPoints,
            'passed' => $totalPoints > 0 ? $score >= $quiz->passing_score : false,
        ]);
    }

    public function render()
    {
        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $quiz = $this->resolveQuiz($enrollment, $this->quiz);
        $progression = app(CourseProgressionService::class);

        $quiz->load(['questions' => function ($query) {
            $query->whereHas('options')->with('options');
        }]);

        $day = $quiz->course_curriculum_section_id
            ? $this->resolveDay($enrollment, (int) $quiz->course_curriculum_section_id)
            : $this->effectiveSections($enrollment)->sortBy('sort_order')->firstOrFail();

        $items = $this->buildDayItemsCollection($enrollment, $day, $progression);
        $allAttempts = QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', auth()->id())
            ->oldest('id')
            ->get();

        $latestAttempt = $allAttempts->last();
        $attemptCount = $allAttempts->count();
        $canResubmit = $quiz->allow_resubmit
            && ($quiz->max_attempts === null || $attemptCount < $quiz->max_attempts);

        $review = $this->quizResults ?? $this->buildQuizReview($quiz, $latestAttempt);

        return view('livewire.dashboard.course-player.quiz', [
            'course' => $this->buildCourseMeta($enrollment),
            'day' => [
                'id' => $day->id,
                'title' => $day->getTranslation('title', app()->getLocale()),
                'route' => route('dashboard.course-player.day', [$this->course_slug, $this->group, $day->id]),
            ],
            'quizRecord' => $quiz,
            'quizData' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'passing_score' => $quiz->passing_score,
                'allow_resubmit' => $quiz->allow_resubmit,
                'max_attempts' => $quiz->max_attempts,
                'questions' => $this->validQuizQuestions($quiz->questions),
            ],
            'quizAttempt' => $latestAttempt,
            'quizAttempts' => $allAttempts,
            'attemptCount' => $attemptCount,
            'canResubmit' => $canResubmit,
            'quizReview' => $review,
            'navLinks' => $this->buildDayNavLinks($enrollment, $day, $items, $progression),
        ])->layout('layouts.app');
    }

    public function retakeQuiz(): void
    {
        $this->quizAnswers = [];
        $this->quizResults = null;
        $this->quizError = null;
        $this->retaking = true;
    }

    /**
     * @param  Collection<int, \App\Models\QuizQuestion>  $questions
     * @return Collection<int, \App\Models\QuizQuestion>
     */
    private function validQuizQuestions(Collection $questions): Collection
    {
        return $questions->filter(fn ($question) => $question->options->isNotEmpty())->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildQuizReview($quiz, ?QuizAttempt $attempt): ?array
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
            ];
        }

        return [
            'score' => $score,
            'total_points' => $totalPoints,
            'items' => $items,
        ];
    }
}
