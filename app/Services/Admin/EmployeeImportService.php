<?php

namespace App\Services\Admin;

use App\Models\Employee;
use App\Models\User;
use App\Notifications\EmployeeCredentialsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Throwable;

class EmployeeImportService
{
    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return [
            'name',
            'email',
            'phone',
            'job_title',
            'role',
            'commission_rate',
            'is_active',
            'department_id',
            'team_id',
            'employee_code',
        ];
    }

    /**
     * @return list<string>
     */
    public function templateSampleRow(): array
    {
        return [
            'John Doe',
            'john.doe@example.com',
            '0500000000',
            'Sales Representative',
            'sales_person',
            '5.00',
            '1',
            '1',
            '1',
            'EMP-001',
        ];
    }

    /**
     * @return array{processed:int,created:int,updated:int,mail_sent:int,mail_failed:int,mail_errors:list<string>,errors:list<string>}
     */
    public function import(string $absolutePath): array
    {
        $rows = $this->rowsFromSpreadsheet($absolutePath);
        $headers = $this->normalizeHeaders(array_shift($rows) ?? []);

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
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
                $result = DB::transaction(fn (): array => $this->importRow($mappedRow));

                if ($result['created']) {
                    $summary['created']++;
                } else {
                    $summary['updated']++;
                }

                if ($result['mail_attempted']) {
                    if ($result['mail_sent']) {
                        $summary['mail_sent']++;
                    } else {
                        $summary['mail_failed']++;
                        if (filled($result['mail_error'])) {
                            $summary['mail_errors'][] = 'Row '.($index + 2).': '.$result['mail_error'];
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
     * @return array{created:bool,mail_attempted:bool,mail_sent:bool,mail_error:string|null}
     */
    private function importRow(array $row): array
    {
        $validated = Validator::make($row, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:sales_person,team_lead,department_manager,sales_manager,support_agent'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable'],
            'department_id' => ['nullable', 'integer'],
            'team_id' => ['nullable', 'integer'],
            'employee_code' => ['nullable', 'string', 'max:100'],
        ])->validate();

        if (array_key_exists('is_active', $validated)) {
            $validated['is_active'] = in_array($validated['is_active'], ['1', 'true', 'yes'], true);
        }

        $email = $validated['email'];
        $existing = Employee::where('email', $email)->first();

        if ($existing) {
            $existing->update(array_filter($validated, fn ($v): bool => $v !== null));

            return ['created' => false, 'mail_attempted' => false, 'mail_sent' => false, 'mail_error' => null];
        }

        $user = User::where('email', $email)->first();
        $plainPassword = null;

        if (! $user) {
            $plainPassword = Str::password(12);
            $user = User::create([
                'name' => $validated['name'],
                'email' => $email,
                'password' => $plainPassword,
            ]);
        }

        $validated['user_id'] = $user->id;

        Employee::create($validated);

        if ($plainPassword === null) {
            return ['created' => true, 'mail_attempted' => false, 'mail_sent' => false, 'mail_error' => null];
        }

        $adminLoginUrl = url('/admin/login');
        $mailSent = false;
        $mailError = null;

        try {
            $user->notify(new EmployeeCredentialsNotification($plainPassword, $adminLoginUrl));
            $mailSent = true;
        } catch (Throwable $e) {
            $mailError = $e->getMessage();
        }

        return ['created' => true, 'mail_attempted' => true, 'mail_sent' => $mailSent, 'mail_error' => $mailError];
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
