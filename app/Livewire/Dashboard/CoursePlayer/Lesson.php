<?php

namespace App\Livewire\Dashboard\CoursePlayer;

use App\Jobs\Nelc\SyncInitializedJob;
use App\Livewire\Dashboard\CoursePlayer\Concerns\InteractsWithCoursePlayer;
use App\Models\ContentProgress;
use App\Models\Lesson as LessonModel;
use App\Services\CourseProgressionService;
use App\Services\NotificationService;
use Livewire\Component;

class Lesson extends Component
{
    use InteractsWithCoursePlayer;

    public string $course_slug;

    public int $group;

    public int $lesson;

    public function mount(string $course_slug, int $group, int $lesson): void
    {
        $this->course_slug = $course_slug;
        $this->group = $group;
        $this->lesson = $lesson;

        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $lessonRecord = $this->resolveLesson($enrollment, $this->lesson);
        $progression = app(CourseProgressionService::class);

        if (! $progression->isLessonAccessible($enrollment, $lessonRecord)) {
            session()->flash('status', __('web.course_player.locked_item_message'));
            $firstAccessibleDay = $progression->firstAccessibleDay($enrollment);

            if ($firstAccessibleDay) {
                $this->redirectRoute('dashboard.course-player.day', [$this->course_slug, $this->group, $firstAccessibleDay->id]);

                return;
            }

            $this->redirectRoute('dashboard.course-player', [$this->course_slug, $this->group]);
        }
    }

    public function render()
    {
        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $lesson = $this->resolveLesson($enrollment, $this->lesson);
        $progression = app(CourseProgressionService::class);

        $day = $this->resolveDay($enrollment, (int) $lesson->course_curriculum_section_id);

        $isFirstView = false;
        $isFirstCourseView = false;
        try {
            $isFirstView = ! ContentProgress::query()
                ->where('user_id', auth()->id())
                ->where('contentable_type', LessonModel::class)
                ->where('contentable_id', $lesson->id)
                ->exists();

            $isFirstCourseView = ! ContentProgress::query()
                ->where('user_id', auth()->id())
                ->where('course_id', $enrollment->course_id)
                ->exists();
        } catch (\Throwable) {
            // if check fails, skip notification but continue
        }

        $this->markContentViewed('lesson', $lesson->id, $enrollment->course_id);

        if ($isFirstCourseView) {
            $this->dispatch('gtm-event', name: 'course_start', data: [
                'item_id' => (string) $enrollment->course_id,
                'item_name' => $enrollment->course?->title,
            ]);
            SyncInitializedJob::dispatch($enrollment->id);
        }

        if ($isFirstView) {
            try {
                app(NotificationService::class)->notifyInstructorsLessonViewed($enrollment, $lesson);
            } catch (\Throwable) {
                // notification failure must not block lesson rendering
            }
        }

        $items = $this->buildDayItemsCollection($enrollment, $day, $progression);

        return view('livewire.dashboard.course-player.lesson', [
            'course' => $this->buildCourseMeta($enrollment),
            'day' => [
                'id' => $day->id,
                'title' => $day->getTranslation('title', app()->getLocale()),
                'route' => route('dashboard.course-player.day', [$this->course_slug, $this->group, $day->id]),
            ],
            'lessonRecord' => $lesson,
            'lessonData' => [
                'id' => $lesson->id,
                'title' => $lesson->getTranslation('title', app()->getLocale()),
                'type' => $lesson->type->value,
                'resource_url' => filled($lesson->video_url) ? $lesson->video_url : $this->attachmentUrl($lesson->pdf_path),
                'content' => filled($lesson->content) ? $lesson->content : __('web.course_player.no_content'),
            ],
            'navLinks' => $this->buildDayNavLinks($enrollment, $day, $items, $progression),
        ])->layout('layouts.app');
    }
}
