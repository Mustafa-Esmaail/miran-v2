<?php

namespace App\Livewire\Dashboard\CoursePlayer;

use App\Livewire\Dashboard\CoursePlayer\Concerns\InteractsWithCoursePlayer;
use App\Models\ContentProgress;
use App\Models\ZoomSessionAttendance;
use App\Services\CourseProgressionService;
use App\Services\SessionStatusService;
use Livewire\Component;

class Session extends Component
{
    use InteractsWithCoursePlayer;

    public string $course_slug;

    public int $group;

    public int $session;

    public function mount(string $course_slug, int $group, int $session): void
    {
        $this->course_slug = $course_slug;
        $this->group = $group;
        $this->session = $session;

        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $sessionRecord = $this->resolveSession($enrollment, $this->session);
        $progression = app(CourseProgressionService::class);

        if (! $progression->isSessionAccessible($enrollment, $sessionRecord)) {
            session()->flash('status', __('web.course_player.locked_item_message'));
            $firstAccessibleDay = $progression->firstAccessibleDay($enrollment);

            if ($firstAccessibleDay) {
                $this->redirectRoute('dashboard.course-player.day', [$this->course_slug, $this->group, $firstAccessibleDay->id]);

                return;
            }

            $this->redirectRoute('dashboard.course-player', [$this->course_slug, $this->group]);
        }
    }

    public function render(SessionStatusService $sessionStatus)
    {
        $enrollment = $this->resolveEnrollment($this->course_slug, $this->group);
        $session = $this->resolveSession($enrollment, $this->session);
        $progression = app(CourseProgressionService::class);

        $day = $this->resolveDay($enrollment, (int) $session->course_curriculum_section_id);

        $isFirstCourseView = false;
        try {
            $isFirstCourseView = ! ContentProgress::query()
                ->where('user_id', auth()->id())
                ->where('course_id', $enrollment->course_id)
                ->exists();
        } catch (\Throwable) {
        }

        $this->markContentViewed('session', $session->id, $enrollment->course_id);

        if ($isFirstCourseView) {
            $this->dispatch('gtm-event', name: 'course_start', data: [
                'item_id' => (string) $enrollment->course_id,
                'item_name' => $enrollment->course?->title,
            ]);
        }

        $items = $this->buildDayItemsCollection($enrollment, $day, $progression);

        $attendanceRecords = $enrollment->id
            ? ZoomSessionAttendance::query()
                ->where('enrollment_id', $enrollment->id)
                ->where('course_group_session_id', $session->id)
                ->orderBy('joined_at')
                ->get(['joined_at', 'left_at', 'duration_minutes'])
                ->map(fn ($r) => (object) [
                    'joined_at' => $r->joined_at,
                    'left_at' => $r->left_at,
                    'duration_minutes' => (int) round($r->duration_minutes / 60),
                ])
            : collect();

        $totalAttendedMinutes = $attendanceRecords->sum('duration_minutes');

        $attachments = collect($session->attachments ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(fn (string $path): array => [
                'name' => basename($path),
                'url' => $this->attachmentUrl($path),
            ])
            ->filter(fn (array $attachment): bool => filled($attachment['url']))
            ->values();

        return view('livewire.dashboard.course-player.session', [
            'course' => $this->buildCourseMeta($enrollment),
            'day' => [
                'id' => $day->id,
                'title' => $day->getTranslation('title', app()->getLocale()),
                'route' => route('dashboard.course-player.day', [$this->course_slug, $this->group, $day->id]),
            ],
            'sessionRecord' => $session,
            'sessionData' => [
                'id' => $session->id,
                'title' => $session->title,
                'start_at' => $session->start_at?->format('Y-m-d H:i'),
                'end_at' => $session->start_at?->copy()?->addMinutes((int) ($session->duration_minutes ?? 0))?->format('Y-m-d H:i'),
                'status' => $sessionStatus->status($session)->value,
                'join_url' => $session->join_url,
                'recording_url' => $session->recording_url,
                'content' => filled($session->content) ? $session->content : null,
                'attachments' => $attachments,
            ],
            'attendanceRecords' => $attendanceRecords,
            'totalAttendedMinutes' => $totalAttendedMinutes,
            'sessionDurationMinutes' => (int) ($session->duration_minutes ?? 0),
            'attendancePct' => (int) ($session->duration_minutes ?? 0) > 0
                ? round($totalAttendedMinutes / (int) $session->duration_minutes * 100)
                : 0,
            'navLinks' => $this->buildDayNavLinks($enrollment, $day, $items, $progression),
        ])->layout('layouts.app');
    }
}
