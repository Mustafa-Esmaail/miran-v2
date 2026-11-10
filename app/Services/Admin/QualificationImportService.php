<?php

namespace App\Services\Admin;

use App\Models\Qualification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use Throwable;

class QualificationImportService
{
    /**
     * @return list<string>
     */
    public function templateHeaders(): array
    {
        return [
            'name_ar',
            'name_en',
            'status',
        ];
    }

    /**
     * @return list<string>
     */
    public function templateSampleRow(): array
    {
        return [
            'بكالوريوس',
            'Bachelor\'s Degree',
            'active',
        ];
    }

    /**
     * @return array{processed:int,created:int,skipped:int,errors:list<string>}
     */
    public function import(string $absolutePath): array
    {
        $rows = $this->rowsFromSpreadsheet($absolutePath);
        $headers = $this->normalizeHeaders(array_shift($rows) ?? []);

        $summary = [
            'processed' => 0,
            'created' => 0,
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
                DB::transaction(fn (): Qualification => $this->importRow($mappedRow));
                $summary['created']++;
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
    private function importRow(array $row): Qualification
    {
        $validated = Validator::make($row, [
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive'],
        ])->validate();

        $nameAr = trim((string) ($validated['name_ar'] ?? ''));
        $nameEn = trim((string) ($validated['name_en'] ?? ''));

        if ($nameAr === '' && $nameEn === '') {
            throw new \InvalidArgumentException('name_ar or name_en is required.');
        }

        $nameAr = $nameAr !== '' ? $nameAr : $nameEn;
        $nameEn = $nameEn !== '' ? $nameEn : $nameAr;

        return Qualification::query()->create([
            'name' => ['ar' => $nameAr, 'en' => $nameEn],
            'status' => $validated['status'] ?? 'active',
        ]);
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
