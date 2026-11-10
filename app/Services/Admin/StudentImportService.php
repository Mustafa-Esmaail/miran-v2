<?php

namespace App\Services\Admin;

use App\Services\StudentCreationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Throwable;

class StudentImportService
{
    public function __construct(private readonly StudentCreationService $students) {}

    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return [
            'name',
            'email',
            'phone',
            'national_id',
            'nationality',
            'birth_date',
            'gender',
            'about',
            'password',
            'course_id',
            'course_group_id',
        ];
    }

    /**
     * @return list<string>
     */
    public function templateSampleRow(): array
    {
        return [
            'Student Name',
            'student@example.com',
            '0500000000',
            '1234567890',
            'Saudi',
            '2000-01-01',
            'male',
            'Imported from admin sheet',
            'Password123!',
            '1',
            '1',
        ];
    }

    /**
     * @return array{processed:int,created:int,updated:int,enrolled:int,skipped:int,mail_sent:int,mail_failed:int,mail_errors:list<string>,errors:list<string>}
     */
    public function import(string $absolutePath): array
    {
        $rows = $this->rowsFromSpreadsheet($absolutePath);
        $headers = $this->normalizeHeaders(array_shift($rows) ?? []);

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'enrolled' => 0,
            'skipped' => 0,
            'mail_sent' => 0,
            'mail_failed' => 0,
            'mail_errors' => [],
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $mappedRow = $this->mapRow($headers, $row);

            if ($this->rowIsEmpty($mappedRow)) {
                continue;
            }

            $summary['processed']++;

            try {
                $imported = DB::transaction(fn (): array => $this->importRow($mappedRow));

                if ($imported['created']) {
                    $summary['created']++;
                } else {
                    $summary['updated']++;
                }

                if ($imported['enrolled']) {
                    $summary['enrolled']++;
                } else {
                    $summary['skipped']++;
                }

                if ($imported['mail_attempted']) {
                    if ($imported['mail_sent']) {
                        $summary['mail_sent']++;
                    } else {
                        $summary['mail_failed']++;

                        if (filled($imported['mail_error'])) {
                            $summary['mail_errors'][] = 'Row '.($index + 2).': '.$imported['mail_error'];
                        }
                    }
                }
            } catch (Throwable $exception) {
                $summary['errors'][] = 'Row '.($index + 2).': '.$exception->getMessage();
            }
        }

        return $summary;
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
     * @param  array<string, string|null>  $row
     * @return array{created:bool,enrolled:bool,mail_attempted:bool,mail_sent:bool,mail_error:string|null}
     */
    private function importRow(array $row): array
    {
        $validated = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'about' => ['nullable', 'string'],
            'password' => ['nullable', 'string', 'min:8'],
            'course_id' => ['required', 'integer'],
            'course_group_id' => ['required', 'integer'],
        ], [
            'course_id.required' => 'course_id is required.',
            'course_group_id.required' => 'course_group_id is required.',
        ])->validate();

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $courseId = (int) $validated['course_id'];
        $courseGroupId = (int) $validated['course_group_id'];
        unset($validated['course_id'], $validated['course_group_id']);

        $result = $this->students->createOrEnroll($validated, $courseId, $courseGroupId);

        return [
            'created' => $result['created'],
            'enrolled' => $result['enrolled'],
            'mail_attempted' => $result['mail_attempted'],
            'mail_sent' => $result['mail_sent'],
            'mail_error' => $result['mail_error'],
        ];
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
