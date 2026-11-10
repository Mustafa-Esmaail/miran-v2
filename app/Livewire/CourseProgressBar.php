<?php

namespace App\Livewire;

use App\Models\ContentProgress;
use App\Models\Enrollment;
use Illuminate\View\View;
use Livewire\Component;

class CourseProgressBar extends Component
{
    public string $courseSlug;

    protected $listeners = ['content-viewed' => '$refresh'];

    public function mount(string $courseSlug): void
    {
        $this->courseSlug = $courseSlug;
    }

    public function render(): View
    {
        $enrollment = Enrollment::query()
            ->where('user_id', auth()->id())
            ->whereHas('course', function ($query) {
                $query->where('slug', $this->courseSlug);
            })
            ->with(['course.lessons', 'course.attachments', 'course.quizzes', 'courseGroup.sessions'])
            ->first();

        if (! $enrollment) {
            return view('livewire.course-progress-bar', [
                'progress' => 0,
            ]);
        }

        $totalContent = $enrollment->course->lessons->count()
            + $enrollment->course->attachments->count()
            + $enrollment->course->quizzes->count()
            + $enrollment->courseGroup->sessions->count();

        $progressCount = ContentProgress::query()
            ->where('user_id', auth()->id())
            ->where('course_id', $enrollment->course->id)
            ->count();

        $progress = $totalContent > 0 ? (int) round(($progressCount / $totalContent) * 100) : 0;
        $progress = max(0, min(100, $progress));

        return view('livewire.course-progress-bar', [
            'progress' => $progress,
        ]);
    }
}
