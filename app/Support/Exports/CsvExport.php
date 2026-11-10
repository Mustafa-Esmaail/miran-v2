<?php

namespace App\Support\Exports;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExport
{
    /**
     * @param  array<int, string>  $headers
     * @param  iterable<array<int, string|int|float|null>>  $rows
     */
    public function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            if (! $handle) {
                return;
            }

            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
