<?php

namespace App\Livewire\Dashboard\CoursePlayer;

use App\Livewire\Dashboard\CoursePlayer\Concerns\InteractsWithCoursePlayer;
use App\Models\CourseGroupSession;
use App\Models\ZoomSessionAttendance;
use App\Services\CourseProgressionService;
use Livewire\Component;

class Attendance extends Component
{
    use InteractsWithCoursePlayer;

    public string $course_slug;

    public int $group;

    public function mount(string $course_slug, int $group): void
    {
        $this->course_slug = $course_slug;
        $this->group = $group;
        $this->resolveEnrollment($course_slug, $group);
    }

    public function render()
    {
        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $progression = app(CourseProgressionService::class);

        $sessionIds = CourseGroupSession::query()
            ->where('course_group_id', $enrollment->courseGroup->id)
            ->ended()
            ->orderBy('start_at')
            ->pluck('id');

        $attendanceBySession = ZoomSessionAttendance::query()
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('course_group_session_id', $sessionIds)
            ->get(['course_group_session_id', 'duration_minutes'])
            ->groupBy('course_group_session_id');

        $sessionRows = $enrollment->courseGroup->sessions
            ->whereIn('id', $sessionIds->all())
            ->sortBy('start_at')
            ->map(function (CourseGroupSession $session) use ($attendanceBySession): array {
                $records = $attendanceBySession->get($session->id, collect());
                $attendedMinutes = (int) round($records->sum('duration_minutes') / 60);
                $totalMinutes = (int) ($session->duration_minutes ?? 0);
                $pct = $totalMinutes > 0 ? round($attendedMinutes / $totalMinutes * 100) : 0;

                return [
                    'id' => $session->id,
                    'title' => $session->title ?: $session->start_at?->format('Y-m-d H:i'),
                    'date' => $session->start_at?->format('Y-m-d'),
                    'time' => $session->start_at?->format('H:i'),
                    'total_minutes' => $totalMinutes,
                    'attended_minutes' => $attendedMinutes,
                    'pct' => $pct,
                    'route' => route('dashboard.course-player.session', [$this->course_slug, $this->group, $session->id]),
                ];
            })
            ->values();

        return view('livewire.dashboard.course-player.attendance', [
            'course' => $this->buildCourseMeta($enrollment),
            'sessionRows' => $sessionRows,
            'navLinks' => $this->buildMainNavLinks($enrollment, $progression),
        ])->layout('layouts.app');
    }
}
