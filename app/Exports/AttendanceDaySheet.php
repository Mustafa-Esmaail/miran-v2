<?php

namespace App\Exports;

use App\Models\CourseGroupSession;
use App\Models\Enrollment;
use App\Models\ZoomSessionAttendance;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceDaySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly int $courseGroupId,
        private readonly float $threshold,
        private readonly ?int $sectionId,
        private readonly string $sheetTitle,
        private readonly ?int $sessionId = null,
    ) {}

    public function title(): string
    {
        return mb_substr($this->sheetTitle, 0, 31);
    }

    public function styles(Worksheet $sheet): void
    {
        $sheet->setRightToLeft(true);
    }

    public function collection(): Collection
    {
        return $this->sessionId
            ? $this->sessionCollection()
            : $this->aggregateCollection();
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        if ($this->sessionId) {
            return [
                'الطالب',
                'البريد الإلكتروني',
                'رقم الجوال',
                'المقرر',
                'المجموعة',
                'تاريخ الجلسة',
                'دقائق الحضور',
                'مدة الجلسة (دقيقة)',
                'الحالة',
            ];
        }

        return [
            'الطالب',
            'البريد الإلكتروني',
            'رقم الجوال',
            'المقرر',
            'المجموعة',
            'الجلسات المحضورة',
            'إجمالي الجلسات',
            'دقائق الحضور',
            'إجمالي الدقائق',
            'نسبة الحضور %',
            'الحالة',
        ];
    }

    /** @param array<string, mixed> $row */
    public function map($row): array
    {
        if ($this->sessionId) {
            return [
                $row['name'],
                $row['email'],
                $row['phone'],
                $row['course_title'],
                $row['course_group'],
                $row['session_date'],
                $row['attended_minutes'],
                $row['total_minutes'],
                $row['status'],
            ];
        }

        return [
            $row['name'],
            $row['email'],
            $row['phone'],
            $row['course_title'],
            $row['course_group'],
            $row['attended'],
            $row['total'],
            $row['attended_minutes'],
            $row['total_minutes'],
            $row['pct'],
            $row['status'],
        ];
    }

    private function aggregateCollection(): Collection
    {
        $sessionQuery = CourseGroupSession::query()
            ->where('course_group_id', $this->courseGroupId)
            ->ended();

        if ($this->sectionId) {
            $sessionQuery->where('course_curriculum_section_id', $this->sectionId);
        }

        $totalSessions = (clone $sessionQuery)->count();
        $totalMinutes = (int) (clone $sessionQuery)->sum('duration_minutes');
        $sessionIds = (clone $sessionQuery)->pluck('id');

        return Enrollment::query()
            ->where('course_group_id', $this->courseGroupId)
            ->with(['user', 'courseGroup.course'])
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
            ->get()
            ->map(function (Enrollment $enrollment) use ($totalSessions, $totalMinutes): array {
                $attended = (int) $enrollment->attended_count;
                $minutes = (int) round($enrollment->attended_minutes / 60);
                $pct = $totalMinutes > 0 ? min(100.0, round($minutes / $totalMinutes * 100, 1)) : 0.0;

                return [
                    'name' => $enrollment->user?->name ?? '',
                    'email' => $enrollment->user?->email ?? '',
                    'phone' => $enrollment->user?->phone ?? '',
                    'course_title' => $enrollment->courseGroup?->course?->getTranslation('title', 'ar') ?? '',
                    'course_group' => $enrollment->courseGroup?->getTranslation('title', 'ar') ?? ('مجموعة #'.$this->courseGroupId),
                    'attended' => $attended,
                    'total' => $totalSessions,
                    'attended_minutes' => $minutes,
                    'total_minutes' => $totalMinutes,
                    'pct' => $pct,
                    'status' => $this->arabicStatus($pct),
                ];
            })
            ->sortByDesc('pct')
            ->values();
    }

    private function sessionCollection(): Collection
    {
        $session = CourseGroupSession::query()
            ->with('courseGroup.course')
            ->find($this->sessionId);

        return Enrollment::query()
            ->where('course_group_id', $this->courseGroupId)
            ->with(['user', 'courseGroup.course'])
            ->selectRaw('enrollments.*')
            ->selectSub(
                ZoomSessionAttendance::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('enrollment_id', 'enrollments.id')
                    ->where('course_group_session_id', $this->sessionId),
                'session_attended'
            )
            ->selectSub(
                ZoomSessionAttendance::query()
                    ->selectRaw('COALESCE(SUM(duration_minutes), 0)')
                    ->whereColumn('enrollment_id', 'enrollments.id')
                    ->where('course_group_session_id', $this->sessionId),
                'session_minutes'
            )
            ->get()
            ->map(function (Enrollment $enrollment) use ($session): array {
                $present = (int) $enrollment->session_attended > 0;

                return [
                    'name' => $enrollment->user?->name ?? '',
                    'email' => $enrollment->user?->email ?? '',
                    'phone' => $enrollment->user?->phone ?? '',
                    'course_title' => $enrollment->courseGroup?->course?->getTranslation('title', 'ar') ?? '',
                    'course_group' => $enrollment->courseGroup?->getTranslation('title', 'ar') ?? ('مجموعة #'.$this->courseGroupId),
                    'session_date' => $session?->start_at?->format('Y-m-d H:i') ?? '',
                    'attended_minutes' => (int) round($enrollment->session_minutes / 60),
                    'total_minutes' => $session?->duration_minutes ?? 0,
                    'status' => $present ? 'حاضر' : 'غائب',
                ];
            })
            ->sortByDesc(fn (array $r) => $r['status'] === 'حاضر')
            ->values();
    }

    private function arabicStatus(float $pct): string
    {
        if ($pct >= $this->threshold) {
            return 'مؤهل';
        }

        return $pct >= 40.0 ? 'في خطر' : 'غير كافٍ';
    }
}
