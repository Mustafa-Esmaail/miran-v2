<?php

namespace App\Services\Admin;

use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestSource;
use App\Models\Employee;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestStatus;
use App\Models\User;
use App\Services\ServiceRequestService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Throwable;

/**
 * Lead import in two steps: analyse() reads a file and labels every row (new, duplicate in
 * the file, duplicate of an existing lead, error) without writing; import() saves the rows.
 *
 * @phpstan-type LeadImportData array{name: string|null, email: string|null, phone: string|null, source: string, status: string|null, priority: string, message: string|null, assigned_to: int|null, team_id: int|null}
 * @phpstan-type LeadImportRow array{row: int, status: 'new'|'duplicate_in_file'|'duplicate_existing'|'error', reason: string|null, data: LeadImportData}
 * @phpstan-type LeadImportCounts array{total: int, new: int, duplicates: int, duplicates_in_file: int, duplicates_existing: int, errors: int}
 */
class ServiceRequestImportService
{
    public const STATUS_NEW = 'new';

    public const STATUS_DUPLICATE_IN_FILE = 'duplicate_in_file';

    public const STATUS_DUPLICATE_EXISTING = 'duplicate_existing';

    public const STATUS_ERROR = 'error';

    private const TEMPLATE_HINT_MARKER = '(required)';

    /** @var array<string, Employee|null> */
    private array $employeesByCode = [];

    /** @var array<string, User|null> */
    private array $usersByEmail = [];

    /** @var array<int, int|null> */
    private array $teamIdsByUserId = [];

    /** @var list<string>|null */
    private ?array $activeStatusSlugs = null;

    private string|false|null $defaultStatusSlug = false;

    /** @return list<string> */
    public function templateHeaders(): array
    {
        return ['name', 'email', 'phone', 'source', 'status', 'priority', 'employee_code', 'assigned_to_email', 'message'];
    }

    /** @return list<string> */
    public function templateSampleRow(): array
    {
        return [
            'Ahmed Ali',
            'ahmed@example.com',
            '0500000000',
            'lead',
            'new',
            'normal',
            'EMP-1',
            'sales@example.com',
            'Interested in advanced training',
        ];
    }

    /** @return list<string> */
    public function templateValidValuesRow(): array
    {
        $sources = implode(' | ', array_column(ServiceRequestSource::cases(), 'value'));
        $statuses = implode(' | ', ServiceRequestStatus::query()->active()->pluck('slug')->all());
        $priorities = implode(' | ', array_column(ServiceRequestPriority::cases(), 'value'));

        return [
            self::TEMPLATE_HINT_MARKER,
            '(optional)',
            '(optional, used to find duplicates)',
            $sources,
            $statuses,
            $priorities,
            '(employee code, optional, checked before the email)',
            '(user email, optional)',
            '(optional)',
        ];
    }

    /**
     * Reads the file and labels every row without writing anything.
     *
     * @return array{rows: list<LeadImportRow>, counts: LeadImportCounts}
     */
    public function analyse(string $absolutePath): array
    {
        $sheetRows = $this->rowsFromSpreadsheet($absolutePath);
        $headers = $this->normalizeHeaders(array_shift($sheetRows) ?? []);

        $rows = [];

        foreach ($sheetRows as $index => $sheetRow) {
            $mapped = $this->mapRow($headers, $sheetRow);

            if ($this->rowIsEmpty($mapped) || ($mapped['name'] ?? null) === self::TEMPLATE_HINT_MARKER) {
                continue;
            }

            $rows[] = $this->analyseRow($index + 2, $mapped);
        }

        $rows = $this->markDuplicates($rows);

        return ['rows' => $rows, 'counts' => $this->countRows($rows)];
    }

    /**
     * Saves the new rows (and the duplicate rows when $skipDuplicates is false).
     *
     * @return array{processed: int, created: int, skipped: int, duplicates: int, duplicates_skipped: int, errors: list<string>}
     */
    public function import(string $absolutePath, bool $skipDuplicates = true): array
    {
        $analysis = $this->analyse($absolutePath);

        $summary = [
            'processed' => count($analysis['rows']),
            'created' => 0,
            'skipped' => 0,
            'duplicates' => $analysis['counts']['duplicates'],
            'duplicates_skipped' => 0,
            'errors' => [],
        ];

        foreach ($analysis['rows'] as $row) {
            if ($row['status'] === self::STATUS_ERROR) {
                $summary['skipped']++;
                $summary['errors'][] = 'Row '.$row['row'].': '.$row['reason'];

                continue;
            }

            if ($row['status'] !== self::STATUS_NEW && $skipDuplicates) {
                $summary['skipped']++;
                $summary['duplicates_skipped']++;

                continue;
            }

            try {
                ServiceRequest::create($row['data']);
                $summary['created']++;
            } catch (Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = 'Row '.$row['row'].': '.$e->getMessage();
            }
        }

        return $summary;
    }

    /**
     * @param  array<string, string|null>  $row
     * @return LeadImportRow
     */
    private function analyseRow(int $rowNumber, array $row): array
    {
        $phone = filled($row['phone'] ?? null) ? $this->normalizePhone((string) $row['phone']) : null;

        $data = [
            'name' => $row['name'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => filled($phone) ? $phone : ($row['phone'] ?? null),
            'source' => $row['source'] ?? ServiceRequestSource::Lead->value,
            'status' => $row['status'] ?? $this->defaultStatusSlug(),
            'priority' => $row['priority'] ?? ServiceRequestPriority::Normal->value,
            'message' => $row['message'] ?? null,
            'assigned_to' => null,
            'team_id' => null,
        ];

        $validator = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'in:'.implode(',', array_column(ServiceRequestSource::cases(), 'value'))],
            'status' => ['nullable', 'in:'.implode(',', $this->activeStatusSlugs())],
            'priority' => ['nullable', 'in:'.implode(',', array_column(ServiceRequestPriority::cases(), 'value'))],
            'employee_code' => ['nullable', 'string', 'max:60'],
            'assigned_to_email' => ['nullable', 'email'],
            'message' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->makeRow($rowNumber, self::STATUS_ERROR, (string) $validator->errors()->first(), $data);
        }

        $assignment = $this->resolveAssignment($row['employee_code'] ?? null, $row['assigned_to_email'] ?? null);

        if (is_string($assignment)) {
            return $this->makeRow($rowNumber, self::STATUS_ERROR, $assignment, $data);
        }

        return $this->makeRow($rowNumber, self::STATUS_NEW, null, [...$data, ...$assignment]);
    }

    /**
     * The assignee and team from the employee code, else from the user email; a string is
     * the reason the row cannot be imported.
     *
     * @return array{assigned_to: int|null, team_id: int|null}|string
     */
    private function resolveAssignment(?string $employeeCode, ?string $email): array|string
    {
        if (filled($employeeCode)) {
            $employee = $this->employeesByCode[$employeeCode] ??= Employee::query()->where('employee_code', $employeeCode)->first();

            if (! $employee) {
                return (string) __('admin.lead_import.reasons.unknown_employee', ['value' => $employeeCode]);
            }

            if (! $employee->user_id) {
                return (string) __('admin.lead_import.reasons.employee_without_user', ['value' => $employeeCode]);
            }

            return ['assigned_to' => (int) $employee->user_id, 'team_id' => $employee->team_id ? (int) $employee->team_id : null];
        }

        if (filled($email)) {
            $user = $this->usersByEmail[$email] ??= User::query()->where('email', $email)->first();

            if (! $user) {
                return (string) __('admin.lead_import.reasons.unknown_employee', ['value' => $email]);
            }

            if (! array_key_exists($user->id, $this->teamIdsByUserId)) {
                $teamId = Employee::query()->where('user_id', $user->id)->whereNotNull('team_id')->value('team_id');
                $this->teamIdsByUserId[$user->id] = $teamId ? (int) $teamId : null;
            }

            return ['assigned_to' => (int) $user->id, 'team_id' => $this->teamIdsByUserId[$user->id]];
        }

        return ['assigned_to' => null, 'team_id' => null];
    }

    /**
     * Marks valid rows whose normalised phone already appeared earlier in the file (the first
     * one wins) or matches an existing lead in any common format.
     *
     * @param  list<LeadImportRow>  $rows
     * @return list<LeadImportRow>
     */
    private function markDuplicates(array $rows): array
    {
        $existingPhones = $this->existingNormalizedPhones($rows);
        $firstRowByPhone = [];

        foreach ($rows as $i => $row) {
            $key = $this->dedupeKey($row);

            if ($row['status'] !== self::STATUS_NEW || $key === null) {
                continue;
            }

            if (isset($firstRowByPhone[$key])) {
                $rows[$i]['status'] = self::STATUS_DUPLICATE_IN_FILE;
                $rows[$i]['reason'] = (string) __('admin.lead_import.reasons.duplicate_in_file', ['row' => $firstRowByPhone[$key]]);

                continue;
            }

            $firstRowByPhone[$key] = $row['row'];

            if (isset($existingPhones[$key])) {
                $rows[$i]['status'] = self::STATUS_DUPLICATE_EXISTING;
                $rows[$i]['reason'] = (string) __('admin.lead_import.reasons.duplicate_existing');
            }
        }

        return $rows;
    }

    /**
     * @param  list<LeadImportRow>  $rows
     * @return array<string, true>
     */
    private function existingNormalizedPhones(array $rows): array
    {
        $variants = [];

        foreach ($rows as $row) {
            if ($row['status'] === self::STATUS_NEW && filled($row['data']['phone'])) {
                array_push($variants, ...ServiceRequestService::phoneVariants((string) $row['data']['phone']));
            }
        }

        $found = [];

        foreach (array_chunk(array_values(array_unique($variants)), 500) as $chunk) {
            ServiceRequest::query()
                ->whereIn('phone', $chunk)
                ->pluck('phone')
                ->each(function (?string $phone) use (&$found): void {
                    $normalized = $this->normalizePhone((string) $phone);

                    if ($normalized !== '') {
                        $found[$normalized] = true;
                    }
                });
        }

        return $found;
    }

    /** @param LeadImportRow $row */
    private function dedupeKey(array $row): ?string
    {
        $phone = $this->normalizePhone((string) ($row['data']['phone'] ?? ''));

        return $phone === '' ? null : $phone;
    }

    /**
     * ServiceRequestService::normalizePhone() after turning an international "00" prefix into
     * "+", so "00966 5…" and "+966 5…" give the same number.
     */
    private function normalizePhone(string $phone): string
    {
        return ServiceRequestService::normalizePhone((string) preg_replace('/^\s*00/', '+', $phone));
    }

    /**
     * @param  list<LeadImportRow>  $rows
     * @return LeadImportCounts
     */
    private function countRows(array $rows): array
    {
        $byStatus = array_count_values(array_column($rows, 'status'));
        $inFile = $byStatus[self::STATUS_DUPLICATE_IN_FILE] ?? 0;
        $existing = $byStatus[self::STATUS_DUPLICATE_EXISTING] ?? 0;

        return [
            'total' => count($rows),
            'new' => $byStatus[self::STATUS_NEW] ?? 0,
            'duplicates' => $inFile + $existing,
            'duplicates_in_file' => $inFile,
            'duplicates_existing' => $existing,
            'errors' => $byStatus[self::STATUS_ERROR] ?? 0,
        ];
    }

    /**
     * @param  'new'|'duplicate_in_file'|'duplicate_existing'|'error'  $status
     * @param  LeadImportData  $data
     * @return LeadImportRow
     */
    private function makeRow(int $rowNumber, string $status, ?string $reason, array $data): array
    {
        return ['row' => $rowNumber, 'status' => $status, 'reason' => $reason, 'data' => $data];
    }

    private function defaultStatusSlug(): ?string
    {
        if ($this->defaultStatusSlug === false) {
            $this->defaultStatusSlug = ServiceRequestStatus::default()?->slug;
        }

        return $this->defaultStatusSlug;
    }

    /** @return list<string> */
    private function activeStatusSlugs(): array
    {
        return $this->activeStatusSlugs ??= ServiceRequestStatus::query()->active()->pluck('slug')->all();
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return list<string>
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn ($h): string => (string) Str::of((string) $h)
            ->replace("\xEF\xBB\xBF", '')
            ->trim()->lower()->replace([' ', '-'], '_'), $headers);
    }

    /**
     * @param  list<string>  $headers
     * @param  array<int, mixed>  $row
     * @return array<string, string|null>
     */
    private function mapRow(array $headers, array $row): array
    {
        $mapped = [];
        foreach ($headers as $i => $header) {
            if ($header === '') {
                continue;
            }
            $value = $row[$i] ?? null;
            $value = is_string($value) ? trim($value) : (filled($value) ? (string) $value : null);
            $mapped[$header] = $value === '' ? null : $value;
        }

        return $mapped;
    }

    /** @param array<string, string|null> $row */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $v) {
            if (filled($v)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, array<int, mixed>> */
    private function rowsFromSpreadsheet(string $absolutePath): array
    {
        $reader = IOFactory::createReaderForFile($absolutePath);
        if ($reader instanceof Csv) {
            $reader->setInputEncoding(Csv::GUESS_ENCODING)->setFallbackEncoding('CP1256');
        }

        return $reader->load($absolutePath)->getActiveSheet()->toArray(null, true, true, false);
    }
}
