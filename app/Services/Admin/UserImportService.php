<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Throwable;

class UserImportService
{
    private const DEFAULT_PASSWORD = 'password';

    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return [
            'name',
            'email',
            'phone',
            'password',
            'role',
            'status',
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
            '',
            '',
            'active',
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
            'password' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,deleted'],
        ])->validate();

        $plainPassword = filled($validated['password'] ?? null)
            ? $validated['password']
            : self::DEFAULT_PASSWORD;

        $email = $validated['email'];
        $existing = User::where('email', $email)->first();
        $isNew = $existing === null;

        $attributes = array_filter([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? null,
        ], fn ($v): bool => $v !== null);

        $attributes['password'] = Hash::make($plainPassword);

        if ($isNew) {
            $attributes['email'] = $email;
            $attributes['status'] = $validated['status'] ?? User::STATUS_ACTIVE;
            $user = User::create($attributes);
        } else {
            $existing->update($attributes);
            $user = $existing->fresh();
        }

        if (filled($validated['role'] ?? null)) {
            $user->syncRoles([$validated['role']]);
        }

        $adminLoginUrl = url('/admin/login');
        $mailSent = false;
        $mailError = null;

        try {
            $user->notify(new UserCredentialsNotification($plainPassword, $adminLoginUrl, $isNew));
            $mailSent = true;
        } catch (Throwable $e) {
            $mailError = $e->getMessage();
        }

        return [
            'created' => $isNew,
            'mail_attempted' => true,
            'mail_sent' => $mailSent,
            'mail_error' => $mailError,
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
