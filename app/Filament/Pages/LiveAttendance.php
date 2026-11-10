<?php

namespace App\Filament\Pages;

use App\Models\CourseGroupSession;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class LiveAttendance extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-signal';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.live-attendance';

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.live_attendance');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_delivery_group');
    }

    public static function canAccess(): bool
    {
        return auth('admin')->user()?->can('zoom.view_live_attendance') ?? false;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.navigation.live_attendance');
    }

    public function refresh(): void
    {
        // Intentionally empty: any Livewire action re-renders the component,
        // which re-runs getLiveSessions() against current data.
    }

    public function getLiveSessions(): Collection
    {
        return CourseGroupSession::query()
            ->live()
            ->with([
                'courseGroup.course',
                'courseGroup.enrollments.user',
                'liveAttendances',
            ])
            ->orderBy('start_at')
            ->get()
            ->map(fn (CourseGroupSession $session) => $this->buildSessionRow($session));
    }

    private function buildSessionRow(CourseGroupSession $session): array
    {
        $liveAttendances = $session->liveAttendances;
        $hostEmail = strtolower(trim((string) ($session->zoom_host_email ?? '')));

        $hostAttendance = $liveAttendances
            ->filter(fn ($a) => $a->role === 'host' || ($hostEmail !== '' && $a->participant_email === $hostEmail))
            ->sortBy('joined_at')
            ->first();

        $instructorStatus = $this->buildInstructorStatus($session, $hostAttendance);

        $enrollments = $session->courseGroup?->enrollments ?? collect();

        $presentAttendances = $liveAttendances->whereIn('status', ['joined', 'joined_before_host']);
        $joinedEnrollmentIds = $presentAttendances->pluck('enrollment_id')->filter()->unique();

        $waitingAttendances = $liveAttendances->where('status', 'waiting_room')->whereNull('left_at');
        $waitingEnrollmentIds = $waitingAttendances->pluck('enrollment_id')->filter()->unique();

        $joinedBeforeHost = $liveAttendances
            ->where('status', 'joined_before_host')
            ->whereNull('left_at')
            ->map(fn ($a) => ['name' => $a->participant_name ?: '-', 'email' => $a->participant_email ?: '-'])
            ->values();

        $waiting = $waitingAttendances
            ->map(fn ($a) => ['name' => $a->participant_name ?: '-', 'email' => $a->participant_email ?: '-'])
            ->values();

        $notJoined = $enrollments
            ->reject(fn ($enrollment) => $joinedEnrollmentIds->contains($enrollment->id) || $waitingEnrollmentIds->contains($enrollment->id))
            ->map(fn ($enrollment) => [
                'name' => $enrollment->user?->name ?? '-',
                'email' => $enrollment->user?->email ?? '-',
            ])
            ->values();

        $attendees = $this->buildAttendeeList($presentAttendances);

        return [
            'session' => $session,
            'course_title' => $session->courseGroup?->course?->getTranslation('title', app()->getLocale()) ?? '-',
            'instructor_status' => $instructorStatus,
            'meeting_started_at' => $session->zoom_actual_started_at,
            'meeting_ended_at' => $session->zoom_actual_ended_at,
            'recording_url' => $session->recording_url,
            'enrolled_count' => $enrollments->count(),
            'joined_count' => $joinedEnrollmentIds->count(),
            'waiting_count' => $waitingEnrollmentIds->count(),
            'waiting' => $waiting,
            'joined_before_host' => $joinedBeforeHost,
            'not_joined' => $notJoined,
            'attendees' => $attendees,
        ];
    }

    /**
     * Groups join/leave segments per participant (by email) so a student who
     * disconnected and rejoined shows every segment's joined/left time.
     */
    private function buildAttendeeList(Collection $presentAttendances): Collection
    {
        return $presentAttendances
            ->sortBy('joined_at')
            ->groupBy(fn ($a) => $a->participant_email ?: 'row-'.$a->id)
            ->map(function (Collection $segments) {
                $first = $segments->first();

                $times = $segments
                    ->map(fn ($segment) => ($segment->joined_at?->format('H:i') ?? '?').' - '.
                        ($segment->left_at?->format('H:i') ?? __('admin.labels.still_in_meeting')))
                    ->implode(' | ');

                return [
                    'name' => $first->participant_name ?: '-',
                    'email' => $first->participant_email ?: '-',
                    'meta' => $segments->count() > 1
                        ? __('admin.labels.rejoined_times', ['count' => $segments->count()]).': '.$times
                        : $times,
                ];
            })
            ->values();
    }

    private function buildInstructorStatus(CourseGroupSession $session, $hostAttendance): array
    {
        if (! $hostAttendance) {
            return [
                'state' => 'not_joined',
                'label' => __('admin.labels.instructor_not_joined'),
                'color' => 'danger',
            ];
        }

        $lateMinutes = $session->start_at->diffInMinutes($hostAttendance->joined_at, false);

        if ($lateMinutes <= 0) {
            return [
                'state' => 'on_time',
                'label' => __('admin.labels.instructor_on_time'),
                'color' => 'success',
            ];
        }

        return [
            'state' => 'late',
            'label' => __('admin.labels.instructor_late', ['minutes' => $lateMinutes]),
            'color' => 'warning',
        ];
    }
}
