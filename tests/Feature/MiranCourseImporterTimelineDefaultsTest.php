<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Services\MiranCourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MiranCourseImporterTimelineDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_populates_timeline_highlights_and_items_when_missing(): void
    {
        $csvPath = storage_path('framework/testing/miran-import.csv');
        File::ensureDirectoryExists(dirname($csvPath));

        $handle = fopen($csvPath, 'w');
        fputcsv($handle, ['ID', 'SKU', 'Name', 'Published', 'Short description', 'Description', 'Categories', 'Images']);
        fputcsv($handle, [
            '1',
            'SKU-100',
            'Timeline Import Course',
            '1',
            'Short summary text',
            'Detailed course description',
            'Business',
            'https://example.test/course.png',
        ]);
        fclose($handle);

        app(MiranCourseImporter::class)->importFromCsv($csvPath);

        $course = Course::query()->firstOrFail();

        $this->assertNotEmpty($course->timeline_items);
        $this->assertNotEmpty($course->timeline_highlights);
        $this->assertCount(3, $course->timeline_items);
        $this->assertCount(3, $course->timeline_highlights);
        $this->assertArrayHasKey('description_en', $course->timeline_items[0]);
        $this->assertStringContainsString('<ul>', (string) ($course->timeline_items[2]['description_en'] ?? ''));

        File::delete($csvPath);
    }
}
