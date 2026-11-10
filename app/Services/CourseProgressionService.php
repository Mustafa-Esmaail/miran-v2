<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\CertificateRequest;
use App\Models\ContentProgress;
use App\Models\CourseAttachment;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Collection;

class CourseProgressionService
{
    /**
     * @var array<string, array{viewed: Collection<int, string>, passed_quizzes: Collection<int, int>}>
     */
    protected array $progressCache = [];

    public function isDayAccessible(Enrollment $enrollment, CourseCurriculumSection $day): bool
    {
        $isFirstDay = $this->orderedDays($enrollment)->first()?->id === $day->id;

        if ($isFirstDay) {
            return true;
        }

        return CertificateRequest::query()
            ->where('user_id', $enrollment->user_id)
            ->where('course_group_id', $enrollment->course_group_id)
            ->exists();
    }

    public function isDayCompleted(Enrollment $enrollment, CourseCurriculumSection $day): bool
    {
        if (! $this->areDayRequirementsCompleted($enrollment, $day)) {
            return false;
        }

        $quizzes = $this->dayQuizzes($enrollment, $day);

        if ($quizzes->isEmpty()) {
            return true;
        }

        return $quizzes->every(fn (Quiz $quiz): bool => $this->isQuizPassed($enrollment, $quiz));
    }

    public function areDayRequirementsCompleted(Enrollment $enrollment, CourseCurriculumSection $day): bool
    {
        $lessonsCompleted = $enrollment->course->lessons
            ->where('course_curriculum_section_id', $day->id)
            ->every(fn (Lesson $lesson): bool => $this->isLessonCompleted($enrollment, $lesson));

        $sessionsCompleted = $enrollment->courseGroup->sessions
            ->where('course_curriculum_section_id', $day->id)
            ->every(fn (CourseGroupSession $session): bool => $this->isSessionCompleted($enrollment, $session));

        return $lessonsCompleted && $sessionsCompleted;
    }

    public function isLessonAccessible(Enrollment $enrollment, Lesson $lesson): bool
    {
        $dayId = $lesson->course_curriculum_section_id;

        if ($dayId === null) {
            return true;
        }

        $day = $this->orderedDays($enrollment)->firstWhere('id', $dayId);

        return $day ? $this->isDayAccessible($enrollment, $day) : true;
    }

    public function isSessionAccessible(Enrollment $enrollment, CourseGroupSession $session): bool
    {
        $dayId = $session->course_curriculum_section_id;

        if ($dayId === null) {
            return true;
        }

        $day = $this->orderedDays($enrollment)->firstWhere('id', $dayId);

        return $day ? $this->isDayAccessible($enrollment, $day) : true;
    }

    public function isAttachmentAccessible(Enrollment $enrollment, CourseAttachment $attachment): bool
    {
        $dayId = $attachment->course_curriculum_section_id;

        if ($dayId === null) {
            return true;
        }

        $day = $this->orderedDays($enrollment)->firstWhere('id', $dayId);

        return $day ? $this->isDayAccessible($enrollment, $day) : true;
    }

    public function isQuizAccessible(Enrollment $enrollment, Quiz $quiz): bool
    {
        return true;
    }

    public function isAssignmentAccessible(Enrollment $enrollment, Assignment $assignment): bool
    {
        $dayId = $assignment->course_curriculum_section_id;

        if ($dayId === null) {
            return true;
        }

        $day = $this->orderedDays($enrollment)->firstWhere('id', $dayId);

        return $day ? $this->isDayAccessible($enrollment, $day) : true;
    }

    public function isLessonCompleted(Enrollment $enrollment, Lesson $lesson): bool
    {
        return $this->viewedContentKeys($enrollment)->contains($this->progressKey(Lesson::class, $lesson->id));
    }

    public function isSessionCompleted(Enrollment $enrollment, CourseGroupSession $session): bool
    {
        return $this->viewedContentKeys($enrollment)->contains($this->progressKey(CourseGroupSession::class, $session->id));
    }

    public function isAttachmentCompleted(Enrollment $enrollment, CourseAttachment $attachment): bool
    {
        return $this->viewedContentKeys($enrollment)->contains($this->progressKey(CourseAttachment::class, $attachment->id));
    }

    public function isAssignmentCompleted(Enrollment $enrollment, Assignment $assignment): bool
    {
        return AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('enrollment_id', $enrollment->id)
            ->exists();
    }

    public function isQuizPassed(Enrollment $enrollment, Quiz $quiz): bool
    {
        return $this->passedQuizIds($enrollment)->contains($quiz->id);
    }

    public function dayState(Enrollment $enrollment, CourseCurriculumSection $day): string
    {
        if ($this->isDayCompleted($enrollment, $day)) {
            return 'completed';
        }

        if ($this->isDayAccessible($enrollment, $day)) {
            return 'available';
        }

        return 'locked';
    }

    public function itemState(Enrollment $enrollment, string $type, mixed $record): string
    {
        $isCompleted = match ($type) {
            'lesson' => $this->isLessonCompleted($enrollment, $record),
            'session' => $this->isSessionCompleted($enrollment, $record),
            'attachment' => $this->isAttachmentCompleted($enrollment, $record),
            'quiz' => $this->isQuizPassed($enrollment, $record),
            'assignment' => $this->isAssignmentCompleted($enrollment, $record),
            default => false,
        };

        if ($isCompleted) {
            return 'completed';
        }

        $isAccessible = match ($type) {
            'lesson' => $this->isLessonAccessible($enrollment, $record),
            'session' => $this->isSessionAccessible($enrollment, $record),
            'attachment' => $this->isAttachmentAccessible($enrollment, $record),
            'quiz' => $this->isQuizAccessible($enrollment, $record),
            'assignment' => $this->isAssignmentAccessible($enrollment, $record),
            default => true,
        };

        return $isAccessible ? 'available' : 'locked';
    }

    public function firstAccessibleDay(Enrollment $enrollment): ?CourseCurriculumSection
    {
        return $this->orderedDays($enrollment)
            ->first(fn (CourseCurriculumSection $day): bool => $this->isDayAccessible($enrollment, $day));
    }

    /**
     * @return Collection<int, CourseCurriculumSection>
     */
    protected function orderedDays(Enrollment $enrollment): Collection
    {
        $groupSections = $enrollment->courseGroup?->curriculumSections ?? collect();

        $sections = $groupSections->isNotEmpty()
            ? $groupSections
            : $enrollment->course->curriculumSections;

        return $sections->sortBy('sort_order')->values();
    }

    /**
     * @return Collection<int, Quiz>
     */
    protected function dayQuizzes(Enrollment $enrollment, CourseCurriculumSection $day): Collection
    {
        return $enrollment->course->quizzes
            ->filter(function (Quiz $quiz) use ($day): bool {
                if ($quiz->course_curriculum_section_id === $day->id) {
                    return true;
                }

                return $quiz->course_curriculum_section_id === null && (int) $day->sort_order === 1;
            })
            ->values();
    }

    protected function quizDay(Enrollment $enrollment, Quiz $quiz): ?CourseCurriculumSection
    {
        if ($quiz->curriculumSection) {
            return $quiz->curriculumSection;
        }

        return $this->orderedDays($enrollment)->firstWhere('sort_order', 1);
    }

    /**
     * @return Collection<int, string>
     */
    protected function viewedContentKeys(Enrollment $enrollment): Collection
    {
        return $this->progressSnapshot($enrollment)['viewed'];
    }

    /**
     * @return Collection<int, int>
     */
    protected function passedQuizIds(Enrollment $enrollment): Collection
    {
        return $this->progressSnapshot($enrollment)['passed_quizzes'];
    }

    /**
     * @return array{viewed: Collection<int, string>, passed_quizzes: Collection<int, int>}
     */
    protected function progressSnapshot(Enrollment $enrollment): array
    {
        $cacheKey = $enrollment->getKey().':'.$enrollment->user_id;

        if (array_key_exists($cacheKey, $this->progressCache)) {
            return $this->progressCache[$cacheKey];
        }

        $viewed = ContentProgress::query()
            ->where('user_id', $enrollment->user_id)
            ->where('course_id', $enrollment->course_id)
            ->get(['contentable_type', 'contentable_id'])
            ->map(fn (ContentProgress $progress): string => $this->progressKey($progress->contentable_type, (int) $progress->contentable_id))
            ->values();

        $quizIds = $enrollment->course->quizzes->pluck('id');

        $passedQuizIds = QuizAttempt::query()
            ->where('user_id', $enrollment->user_id)
            ->whereIn('quiz_id', $quizIds)
            ->get()
            ->filter(function (QuizAttempt $attempt) use ($enrollment): bool {
                $quiz = $enrollment->course->quizzes->firstWhere('id', $attempt->quiz_id);

                return $quiz instanceof Quiz && $attempt->score >= $quiz->passing_score;
            })
            ->pluck('quiz_id')
            ->unique()
            ->values();

        return $this->progressCache[$cacheKey] = [
            'viewed' => $viewed,
            'passed_quizzes' => $passedQuizIds,
        ];
    }

    protected function progressKey(string $type, int $id): string
    {
        return $type.'#'.$id;
    }
}
