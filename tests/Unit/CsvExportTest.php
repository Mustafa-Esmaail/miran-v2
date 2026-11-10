<?php

namespace Tests\Unit;

use App\Support\Exports\CsvExport;
use Illuminate\Support\LazyCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    public function test_it_streams_a_csv_download(): void
    {
        $headers = ['Column A', 'Column B'];
        $rows = LazyCollection::make([
            ['First', 'Row'],
            ['Second', 'Row'],
        ]);

        $response = (new CsvExport)->stream('test.csv', $headers, $rows);

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        ob_start();
        $response->sendContent();
        $output = str_replace("\r\n", "\n", (string) ob_get_clean());

        $this->assertStringContainsString("\"Column A\",\"Column B\"\n", $output);
        $this->assertStringContainsString("First,Row\n", $output);
    }
}
