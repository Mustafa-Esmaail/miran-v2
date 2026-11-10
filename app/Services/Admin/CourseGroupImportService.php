<?php

namespace App\Services\Admin;

use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\CourseInstructor;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Throwable;

class CourseGroupImportService
{
    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return [
            'id',
            'course_id',
            'trainer_id',
            'group_title_ar',
            'group_title_en',
            'capacity',
            'status',
            'start_at',
            'end_at',
            'enrollment_deadline_at',
            'days_count',
            'join_link',
        ];
    }

    /**
     * @return list<string>
     */
    public function templateSampleRow(): array
    {
        return [
            '',
            '1',
            '',
            'مجموعة صباحية',
            'Morning Group',
            '30',
            'open',
            '2026-05-01 09:00:00',
            '2026-05-30 12:00:00',
            '2026-04-30 23:59:59',
            '5',
            'https://example.com/join/abc',
        ];
    }

    /**
     * @return array{processed:int,created:int,updated:int,skipped:int,errors:list<string>}
     */
    public function import(string $absolutePath): array
    {
        $rows = $this->rowsFromSpreadsheet($absolutePath);
        $headers = $this->normalizeHeaders(array_shift($rows) ?? []);

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $mappedRow = $this->mapRow($headers, $row);

            if ($this->rowIsEmpty($mappedRow)) {
                continue;
            }

            $summary['processed']++;

            try {
                $group = DB::transaction(fn (): CourseGroup => $this->importRow($mappedRow));

                if ($group->wasRecentlyCreated) {
                    $summary['created']++;
                } else {
                    $summary['updated']++;
                }
            } catch (Throwable $exception) {
                $summary['skipped']++;
                $summary['errors'][] = 'Row '.($index + 2).': '.$exception->getMessage();
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function importRow(array $row): CourseGroup
    {
        $validated = Validator::make($row, [
            'id' => ['nullable', 'integer', 'exists:course_groups,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'trainer_id' => ['nullable', 'integer', 'exists:users,id'],
            'group_title_ar' => ['nullable', 'string', 'max:255'],
            'group_title_en' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:open,closed,archived'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date'],
            'enrollment_deadline_at' => ['nullable', 'date'],
            'days_count' => ['required', 'integer', 'min:1'],
            'join_link' => ['nullable', 'url'],
            'join_url' => ['nullable', 'url'],
        ], [
            'course_id.required' => 'course_id is required.',
            'days_count.required' => 'days_count is required.',
        ])->validate();

        $course = Course::query()->findOrFail((int) $validated['course_id']);

        $isUpdate = filled($validated['id'] ?? null);

        if (! $isUpdate && filled($validated['start_at'] ?? null)) {
            $importDate = Carbon::parse($validated['start_at'])->format('Y-m-d');

            $existingGroup = CourseGroup::query()
                ->where('course_id', $course->id)
                ->whereDate('start_at', $importDate)
                ->first();

            if ($existingGroup) {
                $validated['id'] = $existingGroup->id;
                $isUpdate = true;
            }
        }

        $titleAr = trim((string) ($validated['group_title_ar'] ?? ''));
        $titleEn = trim((string) ($validated['group_title_en'] ?? ''));

        $attributes = [
            'course_id' => $course->id,
        ];

        if ($isUpdate) {
            if ($titleAr !== '' || $titleEn !== '') {
                $attributes['title'] = [
                    'ar' => $titleAr !== '' ? $titleAr : $titleEn,
                    'en' => $titleEn !== '' ? $titleEn : $titleAr,
                ];
            }

            foreach (['capacity', 'status', 'start_at', 'end_at', 'enrollment_deadline_at'] as $field) {
                if (array_key_exists($field, $row) && filled($row[$field])) {
                    $attributes[$field] = $validated[$field];
                }
            }

            $group = CourseGroup::query()->updateOrCreate(
                ['id' => (int) $validated['id'], 'course_id' => $course->id],
                $attributes
            );
        } else {
            if ($titleAr === '' && $titleEn === '') {
                $titleAr = 'مجموعة '.now()->format('YmdHis');
                $titleEn = 'Group '.now()->format('YmdHis');
            } elseif ($titleAr === '') {
                $titleAr = $titleEn;
            } elseif ($titleEn === '') {
                $titleEn = $titleAr;
            }

            $attributes['title'] = ['ar' => $titleAr, 'en' => $titleEn];
            $attributes['capacity'] = $validated['capacity'] ?? null;
            $attributes['status'] = $validated['status'] ?? 'open';
            $attributes['start_at'] = $validated['start_at'] ?? null;
            $attributes['end_at'] = $validated['end_at'] ?? null;
            $attributes['enrollment_deadline_at'] = $validated['enrollment_deadline_at'] ?? null;

            $group = CourseGroup::query()->create($attributes);
        }

        if (filled($validated['trainer_id'] ?? null)) {
            $trainer = User::query()->findOrFail((int) $validated['trainer_id']);

            if (! $trainer->hasRole('instructor')) {
                throw new \InvalidArgumentException("trainer_id {$trainer->id} is not an instructor.");
            }

            CourseInstructor::firstOrCreate([
                'course_id' => $course->id,
                'course_group_id' => $group->id,
                'user_id' => $trainer->id,
            ]);
        }

        $daysCount = (int) $validated['days_count'];
        $joinUrl = (string) ($validated['join_link'] ?? $validated['join_url'] ?? '');
        $sessionBaseStartAt = $group->start_at ?? now();

        for ($dayIndex = 1; $dayIndex <= $daysCount; $dayIndex++) {
            $dayTitleAr = 'اليوم '.$this->arabicOrdinal($dayIndex);

            $section = CourseCurriculumSection::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'course_group_id' => $group->id,
                    'sort_order' => $dayIndex,
                ],
                [
                    'title' => [
                        'ar' => $dayTitleAr,
                        'en' => 'Day '.$dayIndex,
                    ],
                    'description' => null,
                ]
            );

            CourseGroupSession::query()->updateOrCreate(
                [
                    'course_group_id' => $group->id,
                    'course_curriculum_section_id' => $section->id,
                ],
                [
                    'title' => 'المحاضره '.$this->arabicOrdinal($dayIndex),
                    'content' => null,
                    'start_at' => $sessionBaseStartAt->copy()->addDays($dayIndex - 1),
                    'duration_minutes' => 120,
                    'join_url' => $joinUrl !== '' ? $joinUrl : null,
                ]
            );
        }

        return $group;
    }

    private function arabicOrdinal(int $number): string
    {
        $map = [
            1 => 'الاول',
            2 => 'الثاني',
            3 => 'الثالث',
            4 => 'الرابع',
            5 => 'الخامس',
            6 => 'السادس',
            7 => 'السابع',
            8 => 'الثامن',
            9 => 'التاسع',
            10 => 'العاشر',
            11 => 'الحادي عشر',
            12 => 'الثاني عشر',
            13 => 'الثالث عشر',
            14 => 'الرابع عشر',
            15 => 'الخامس عشر',
            16 => 'السادس عشر',
            17 => 'السابع عشر',
            18 => 'الثامن عشر',
            19 => 'التاسع عشر',
            20 => 'العشرون',
        ];

        return $map[$number] ?? (string) $number;
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return list<string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header): string {
            return (string) Str::of((string) $header)
                ->replace("\xEF\xBB\xBF", '')
                ->trim()
                ->lower()
                ->replace([' ', '-'], '_');
        }, $headers);
    }

    /**
     * @param  list<string>  $headers
     * @param  array<int, mixed>  $row
     * @return array<string, string|null>
     */
    private function mapRow(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            if ($header === '') {
                continue;
            }

            $value = $row[$index] ?? null;
            $mapped[$header] = is_string($value) ? trim($value) : (filled($value) ? (string) $value : null);
        }

        return $mapped;
    }

    /**
     * @param  array<string, string|null>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (filled($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function rowsFromSpreadsheet(string $absolutePath): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);

        if ($reader instanceof Csv) {
            $reader
                ->setInputEncoding(Csv::GUESS_ENCODING)
                ->setFallbackEncoding('CP1256');
        }

        $spreadsheet = $reader->load($absolutePath);

        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
    }
}
