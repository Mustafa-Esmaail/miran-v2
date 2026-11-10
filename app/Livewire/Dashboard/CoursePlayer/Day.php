<?php

namespace App\Livewire\Dashboard\CoursePlayer;

use App\Livewire\Dashboard\CoursePlayer\Concerns\InteractsWithCoursePlayer;
use App\Services\CourseProgressionService;
use Livewire\Component;

class Day extends Component
{
    use InteractsWithCoursePlayer;

    public string $course_slug;

    public int $group;

    public int $dayId;

    public function mount(string $course_slug, int $group, int $day): void
    {
        $this->course_slug = $course_slug;
        $this->group = $group;
        $this->dayId = $day;

        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $dayRecord = $this->resolveDay($enrollment, $this->dayId);
        $progression = app(CourseProgressionService::class);

        if (! $progression->isDayAccessible($enrollment, $dayRecord)) {
            session()->flash('status', __('web.course_player.locked_day_message'));
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
        $day = $this->resolveDay($enrollment, $this->dayId);
        $progression = app(CourseProgressionService::class);

        $items = $this->buildDayItemsCollection($enrollment, $day, $progression);

        return view('livewire.dashboard.course-player.day', [
            'course' => $this->buildCourseMeta($enrollment),
            'dayRecord' => $day,
            'day' => [
                'id' => $day->id,
                'title' => $day->getTranslation('title', app()->getLocale()),
                'description' => filled($day->description) ? $day->getTranslation('description', app()->getLocale()) : null,
                'state' => $progression->dayState($enrollment, $day),
            ],
            'items' => $items,
            'navLinks' => $this->buildDayNavLinks($enrollment, $day, $items, $progression),
        ])->layout('layouts.app');
    }
}
