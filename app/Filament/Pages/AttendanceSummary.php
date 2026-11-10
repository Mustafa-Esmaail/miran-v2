<?php

namespace App\Filament\Pages;

use App\Exports\AttendanceSummaryExport;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\ZoomSessionAttendance;
use App\Services\ZoomService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class AttendanceSummary extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.attendance-summary';

    public ?int $selectedCourseId = null;

    public ?int $selectedCourseGroupId = null;

    public ?int $selectedSectionId = null;

    public ?int $selectedSessionId = null;

    public int $threshold = 60;

    public bool $showAssignModal = false;

    /** @var array<int> */
    public array $pendingRecordIds = [];

    public string $pendingGuestLabel = '';

    public ?int $assignToEnrollmentId = null;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.attendance_summary');
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
        return auth('admin')->user()?->can('attendance.viewAny') ?? false;
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('admin.navigation.attendance_summary');
    }

    public function updatedSelectedCourseId(): void
    {
        $this->selectedCourseGroupId = null;
        $this->selectedSectionId = null;
        $this->selectedSessionId = null;
    }

    public function updatedSelectedCourseGroupId(): void
    {
        $this->selectedSectionId = null;
        $this->selectedSessionId = null;
    }

    public function updatedSelectedSectionId(): void
    {
        $this->selectedSessionId = null;
    }

    public function selectSection(?int $sectionId): void
    {
        $this->selectedSectionId = $sectionId;
        $this->selectedSessionId = null;
    }

    public function getCourseOptions(): array
    {
        return Course::query()
            ->orderBy('title->en')
            ->get()
            ->mapWithKeys(fn (Course $c) => [$c->id => $c->getTranslation('title', app()->getLocale())])
            ->all();
    }

    public function getCourseGroupOptions(): array
    {
        if (! $this->selectedCourseId) {
            return [];
        }

        return CourseGroup::query()
            ->where('course_id', $this->selectedCourseId)
            ->get()
            ->mapWithKeys(fn (CourseGroup $g) => [
                $g->id => $g->getTranslation('title', app()->getLocale()) ?: ('Group #'.$g->id),
            ])
            ->all();
    }

    public function getSectionOptions(): array
    {
        if (! $this->selectedCourseGroupId) {
            return [];
        }

        return CourseCurriculumSection::query()
            ->where('course_group_id', $this->selectedCourseGroupId)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (CourseCurriculumSection $s) => [
                $s->id => $s->getTranslation('title', app()->getLocale()) ?: ('Day #'.$s->sort_order),
            ])
            ->all();
    }

    public function getSessionOptions(): array
    {
        if (! $this->selectedCourseGroupId) {
            return [];
        }

        $query = CourseGroupSession::query()
            ->where('course_group_id', $this->selectedCourseGroupId)
            ->ended()
            ->orderBy('start_at');

        if ($this->selectedSectionId) {
            $query->where('course_curriculum_section_id', $this->selectedSectionId);
        }

        return $query->get()
            ->mapWithKeys(fn (CourseGroupSession $s) => [
                $s->id => $s->start_at->format('Y-m-d').' — '.($s->title ?: $s->start_at->format('H:i')),
            ])
            ->all();
    }

    public function getAttendanceRows(): Collection
    {
        if (! $this->selectedCourseGroupId) {
            return collect();
        }

        return $this->selectedSessionId
            ? $this->getSessionRows()
            : $this->getAggregateRows();
    }

    public function getStats(): array
    {
        // Stats reflect enrolled students only, not unregistered Zoom guests
        $rows = $this->getAttendanceRows()->where('is_guest', false);

        if ($rows->isEmpty()) {
            return ['total' => 0, 'met' => 0, 'no_attendance' => 0, 'avg' => 0.0];
        }

        if ($this->selectedSessionId) {
            return [
                'total' => $rows->count(),
                'met' => $rows->where('present', true)->count(),
                'no_attendance' => $rows->where('present', false)->count(),
                'avg' => round($rows->avg('pct'), 1),
            ];
        }

        return [
            'total' => $rows->count(),
            'met' => $rows->where('pct', '>=', $this->threshold)->count(),
            'no_attendance' => $rows->where('attended', 0)->count(),
            'avg' => round($rows->avg('pct'), 1),
        ];
    }

    public function syncFromZoom(ZoomService $zoom): void
    {
        if (! $this->selectedCourseGroupId) {
            return;
        }

        $sessionQuery = CourseGroupSession::query()
            ->where('course_group_id', $this->selectedCourseGroupId)
            ->ended()
            ->whereNotNull('zoom_meeting_uuid');

        if ($this->selectedSectionId) {
            $sessionQuery->where('course_curriculum_section_id', $this->selectedSectionId);
        }

        $sessions = $sessionQuery->get();

        if ($sessions->isEmpty()) {
            Notification::make()
                ->title(__('admin.labels.no_sessions_to_sync'))
                ->warning()
                ->send();

            return;
        }

        $synced = 0;
        $failed = 0;

        foreach ($sessions as $session) {
            try {
                $zoom->syncSessionAttendance($session);
                $synced++;
            } catch (Throwable) {
                $failed++;
            }
        }

        if ($failed === 0) {
            Notification::make()
                ->title(__('admin.labels.sync_complete', ['count' => $synced]))
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title(__('admin.labels.sync_partial', ['synced' => $synced, 'failed' => $failed]))
                ->warning()
                ->send();
        }
    }

    public function exportXlsx(): BinaryFileResponse
    {
        $timestamp = now()->format('Ymd-His');

        return Excel::download(
            new AttendanceSummaryExport(
                courseGroupId: $this->selectedCourseGroupId,
                threshold: $this->threshold,
                sectionId: $this->selectedSectionId,
                sessionId: $this->selectedSessionId,
            ),
            "miran-attendance-{$timestamp}.xlsx"
        );
    }

    private function getAggregateRows(): Collection
    {
        $sessionQuery = CourseGroupSession::query()
            ->where('course_group_id', $this->selectedCourseGroupId)
            ->ended();

        if ($this->selectedSectionId) {
            $sessionQuery->where('course_curriculum_section_id', $this->selectedSectionId);
        }

        $totalSessions = (clone $sessionQuery)->count();
        $totalMinutes = (int) (clone $sessionQuery)->sum('duration_minutes');
        $sessionIds = (clone $sessionQuery)->pluck('id');

        $enrollments = Enrollment::query()
            ->where('course_group_id', $this->selectedCourseGroupId)
            ->with('user')
            ->selectRaw('enrollments.*')
            ->selectSub(
                ZoomSessionAttendance::query()
                    ->selectRaw('COUNT(DISTINCT course_group_session_id)')
                    ->whereColumn('enrollment_id', 'enrollments.id')
                    ->whereIn('course_group_session_id', $sessionIds),
                'attended_count'
            )
            ->selectSub(
                ZoomSessionAttendance::query()
                    ->selectRaw('COALESCE(SUM(duration_minutes), 0)')
                    ->whereColumn('enrollment_id', 'enrollments.id')
                    ->whereIn('course_group_session_id', $sessionIds),
                'attended_minutes'
            )
            ->get();

        $enrolledRows = $enrollments->map(function (Enrollment $enrollment) use ($totalSessions, $totalMinutes): array {
            $attended = (int) $enrollment->attended_count;
            $minutes = (int) round($enrollment->attended_minutes / 60);
            $pct = $totalMinutes > 0 ? min(100.0, round($minutes / $totalMinutes * 100, 1)) : 0.0;

            return [
                'name' => $enrollment->user?->name ?? '-',
                'email' => $enrollment->user?->email ?? '-',
                'phone' => $enrollment->user?->phone ?? '-',
                'attended' => $attended,
                'total' => $totalSessions,
                'attended_minutes' => $minutes,
                'total_minutes' => $totalMinutes,
                'pct' => $pct,
                'present' => null,
                'badge_color' => $this->badgeColor($pct),
                'badge_label' => $this->badgeLabel($pct),
                'mode' => 'aggregate',
                'is_guest' => false,
            ];
        });

        $guestRows = ZoomSessionAttendance::query()
            ->whereNull('enrollment_id')
            ->whereIn('course_group_session_id', $sessionIds)
            ->get()
            ->groupBy(fn (ZoomSessionAttendance $r): string => $r->participant_email ?: ('__'.$r->participant_name))
            ->map(function (Collection $records) use ($totalSessions, $totalMinutes): array {
                $first = $records->first();
                $attended = $records->pluck('course_group_session_id')->unique()->count();
                $minutes = (int) round($records->sum('duration_minutes') / 60);
                $pct = $totalMinutes > 0 ? min(100.0, round($minutes / $totalMinutes * 100, 1)) : 0.0;

                return [
                    'name' => $first->participant_name ?: '-',
                    'email' => $first->participant_email ?: '-',
                    'phone' => '-',
                    'attended' => $attended,
                    'total' => $totalSessions,
                    'attended_minutes' => $minutes,
                    'total_minutes' => $totalMinutes,
                    'pct' => $pct,
                    'present' => null,
                    'badge_color' => $this->badgeColor($pct),
                    'badge_label' => $this->badgeLabel($pct),
                    'mode' => 'aggregate',
                    'is_guest' => true,
                    'guest_record_ids' => $records->pluck('id')->toArray(),
                ];
            });

        return $enrolledRows->concat($guestRows)->sortByDesc('pct')->values();
    }

    private function getSessionRows(): Collection
    {
        $session = CourseGroupSession::query()->find($this->selectedSessionId);

        $enrollments = Enrollment::query()
            ->where('course_group_id', $this->selectedCourseGroupId)
            ->with('user')
            ->selectRaw('enrollments.*')
            ->selectSub(
                ZoomSessionAttendance::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('enrollment_id', 'enrollments.id')
                    ->where('course_group_session_id', $this->selectedSessionId),
                'session_attended'
            )
            ->selectSub(
                ZoomSessionAttendance::query()
                    ->selectRaw('COALESCE(SUM(duration_minutes), 0)')
                    ->whereColumn('enrollment_id', 'enrollments.id')
                    ->where('course_group_session_id', $this->selectedSessionId),
                'session_minutes'
            )
            ->get();

        $enrolledRows = $enrollments->map(function (Enrollment $enrollment) use ($session): array {
            $present = (int) $enrollment->session_attended > 0;
            $minutes = (int) round($enrollment->session_minutes / 60);

            return [
                'name' => $enrollment->user?->name ?? '-',
                'email' => $enrollment->user?->email ?? '-',
                'phone' => $enrollment->user?->phone ?? '-',
                'attended' => $present ? 1 : 0,
                'total' => 1,
                'attended_minutes' => $minutes,
                'total_minutes' => $session?->duration_minutes ?? 0,
                'pct' => $present ? 100.0 : 0.0,
                'present' => $present,
                'badge_color' => $present ? 'success' : 'danger',
                'badge_label' => $present ? __('admin.labels.present') : __('admin.labels.absent'),
                'mode' => 'session',
                'is_guest' => false,
            ];
        });

        $guestRows = ZoomSessionAttendance::query()
            ->whereNull('enrollment_id')
            ->where('course_group_session_id', $this->selectedSessionId)
            ->get()
            ->map(function (ZoomSessionAttendance $record) use ($session): array {
                $present = $record->duration_minutes > 0;

                return [
                    'name' => $record->participant_name ?: '-',
                    'email' => $record->participant_email ?: '-',
                    'phone' => '-',
                    'attended' => $present ? 1 : 0,
                    'total' => 1,
                    'attended_minutes' => (int) round($record->duration_minutes / 60),
                    'total_minutes' => $session?->duration_minutes ?? 0,
                    'pct' => $present ? 100.0 : 0.0,
                    'present' => $present,
                    'badge_color' => $present ? 'success' : 'danger',
                    'badge_label' => $present ? __('admin.labels.present') : __('admin.labels.absent'),
                    'mode' => 'session',
                    'is_guest' => true,
                    'guest_record_ids' => [$record->id],
                ];
            });

        return $enrolledRows->concat($guestRows)->sortByDesc('present')->values();
    }

    /**
     * @param  array<int>  $recordIds
     */
    public function openCorrectAttendance(array $recordIds, string $label): void
    {
        $this->pendingRecordIds = $recordIds;
        $this->pendingGuestLabel = $label;
        $this->assignToEnrollmentId = null;
        $this->showAssignModal = true;
        $this->dispatch('open-modal', id: 'correct-attendance');
    }

    public function closeCorrectAttendance(): void
    {
        $this->showAssignModal = false;
        $this->pendingRecordIds = [];
        $this->pendingGuestLabel = '';
        $this->assignToEnrollmentId = null;
        $this->dispatch('close-modal', id: 'correct-attendance');
    }

    public function confirmCorrectAttendance(): void
    {
        if (! $this->assignToEnrollmentId || empty($this->pendingRecordIds)) {
            return;
        }

        $enrollment = Enrollment::query()
            ->where('id', $this->assignToEnrollmentId)
            ->where('course_group_id', $this->selectedCourseGroupId)
            ->first();

        if (! $enrollment) {
            Notification::make()
                ->title(__('admin.labels.invalid_enrollment'))
                ->danger()
                ->send();

            return;
        }

        ZoomSessionAttendance::query()
            ->whereIn('id', $this->pendingRecordIds)
            ->whereNull('enrollment_id')
            ->update([
                'user_id' => $enrollment->user_id,
                'enrollment_id' => $enrollment->id,
            ]);

        $this->closeCorrectAttendance();

        Notification::make()
            ->title(__('admin.labels.attendance_corrected'))
            ->success()
            ->send();
    }

    public function getEnrolledUserOptions(): array
    {
        if (! $this->selectedCourseGroupId) {
            return [];
        }

        return Enrollment::query()
            ->where('course_group_id', $this->selectedCourseGroupId)
            ->with('user')
            ->get()
            ->mapWithKeys(fn (Enrollment $e): array => [
                $e->id => ($e->user?->name ?? '-').' — '.($e->user?->email ?? ''),
            ])
            ->all();
    }

    private function badgeColor(float $pct): string
    {
        if ($pct >= $this->threshold) {
            return 'success';
        }

        return $pct >= 40.0 ? 'warning' : 'danger';
    }

    private function badgeLabel(float $pct): string
    {
        if ($pct >= $this->threshold) {
            return __('admin.labels.eligible');
        }

        return $pct >= 40.0 ? __('admin.labels.at_risk') : __('admin.labels.insufficient');
    }
}
