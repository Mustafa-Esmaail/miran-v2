<?php

namespace App\Services\Admin;

use App\Enums\CrmTaskPriority;
use App\Enums\CrmTaskStatus;
use App\Models\CrmTask;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Throwable;

class CrmTaskImportService
{
    /** @return list<string> */
    public function templateHeaders(): array
    {
        return ['title', 'entity_type', 'entity_id', 'employee_email', 'priority', 'status', 'description', 'due_at'];
    }

    /** @return list<string> */
    public function templateSampleRow(): array
    {
        return [
            'Follow up with client',
            'deal',
            '1',
            'sales@example.com',
            'high',
            'todo',
            'Call the client about the proposal',
            '2026-05-01 10:00',
        ];
    }

    /** @return list<string> */
    public function templateValidValuesRow(): array
    {
        $priorities = implode(' | ', array_column(CrmTaskPriority::cases(), 'value'));
        $statuses = implode(' | ', array_column(CrmTaskStatus::cases(), 'value'));

        return [
            '(required)',
            'service_request | deal',
            '(required, integer)',
            '(employee email, optional)',
            $priorities,
            $statuses,
            '(optional)',
            'YYYY-MM-DD HH:MM (optional)',
        ];
    }

    /**
     * @return array{processed:int,created:int,skipped:int,errors:list<string>}
     */
    public function import(string $absolutePath): array
    {
        $rows = $this->rowsFromSpreadsheet($absolutePath);
        $headers = $this->normalizeHeaders(array_shift($rows) ?? []);

        $summary = ['processed' => 0, 'created' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            $mapped = $this->mapRow($headers, $row);

            if ($this->rowIsEmpty($mapped)) {
                continue;
            }

            $summary['processed']++;

            try {
                $this->importRow($mapped);
                $summary['created']++;
            } catch (Throwable $e) {
                $summary['skipped']++;
                $summary['errors'][] = 'Row '.($index + 2).': '.$e->getMessage();
            }
        }

        return $summary;
    }

    /** @param array<string, string|null> $row */
    private function importRow(array $row): void
    {
        $validated = Validator::make($row, [
            'title' => ['required', 'string', 'max:255'],
            'entity_type' => ['required', 'in:service_request,deal'],
            'entity_id' => ['required', 'integer', 'min:1'],
            'employee_email' => ['nullable', 'email'],
            'priority' => ['nullable', 'in:'.implode(',', array_column(CrmTaskPriority::cases(), 'value'))],
            'status' => ['nullable', 'in:'.implode(',', array_column(CrmTaskStatus::cases(), 'value'))],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
        ])->validate();

        $entityClass = match ($validated['entity_type']) {
            'service_request' => ServiceRequest::class,
            'deal' => Deal::class,
        };

        $entityId = (int) $validated['entity_id'];
        $entityClass::findOrFail($entityId);

        $employeeId = null;
        if (filled($validated['employee_email'] ?? null)) {
            $employee = Employee::query()
                ->whereHas('user', fn ($q) => $q->where('email', $validated['employee_email']))
                ->first();
            $employeeId = $employee?->id;
        }

        CrmTask::create([
            'entity_type' => $entityClass,
            'entity_id' => $entityId,
            'employee_id' => $employeeId,
            'title' => $validated['title'],
            'priority' => $validated['priority'] ?? CrmTaskPriority::Normal->value,
            'status' => $validated['status'] ?? CrmTaskStatus::Todo->value,
            'description' => $validated['description'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
        ]);
    }

    /** @return list<string> */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(fn ($h): string => (string) Str::of((string) $h)
            ->replace("\xEF\xBB\xBF", '')
            ->trim()->lower()->replace([' ', '-'], '_'), $headers);
    }

    /** @return array<string, string|null> */
    private function mapRow(array $headers, array $row): array
    {
        $mapped = [];
        foreach ($headers as $i => $header) {
            if ($header === '') {
                continue;
            }
            $value = $row[$i] ?? null;
            $mapped[$header] = is_string($value) ? trim($value) : (filled($value) ? (string) $value : null);
        }

        return $mapped;
    }

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
