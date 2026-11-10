<?php

namespace App\Exports;

use App\Models\CourseCurriculumSection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AttendanceSummaryExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $courseGroupId,
        private readonly float $threshold = 60.0,
        private readonly ?int $sectionId = null,
        private readonly ?int $sessionId = null,
    ) {}

    /** @return array<int, AttendanceDaySheet> */
    public function sheets(): array
    {
        if ($this->sessionId) {
            return [
                new AttendanceDaySheet(
                    courseGroupId: $this->courseGroupId,
                    threshold: $this->threshold,
                    sectionId: null,
                    sheetTitle: 'الجلسة',
                    sessionId: $this->sessionId,
                ),
            ];
        }

        $sheets = [
            new AttendanceDaySheet(
                courseGroupId: $this->courseGroupId,
                threshold: $this->threshold,
                sectionId: $this->sectionId,
                sheetTitle: 'كل الأيام',
            ),
        ];

        if (! $this->sectionId) {
            $sections = CourseCurriculumSection::query()
                ->where('course_group_id', $this->courseGroupId)
                ->orderBy('sort_order')
                ->get();

            foreach ($sections as $section) {
                $sheets[] = new AttendanceDaySheet(
                    courseGroupId: $this->courseGroupId,
                    threshold: $this->threshold,
                    sectionId: $section->id,
                    sheetTitle: $section->getTranslation('title', 'ar') ?: ('اليوم '.$section->sort_order),
                );
            }
        }

        return $sheets;
    }
}
