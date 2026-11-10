<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class DateTimeDisplayFormatTest extends TestCase
{
    public function test_filament_resources_do_not_use_seconds_in_datetime_columns(): void
    {
        $resourceFiles = $this->phpFilesIn(base_path('app/Filament/Admin/Resources'));

        foreach ($resourceFiles as $file) {
            $contents = file_get_contents($file);

            $this->assertNotFalse($contents);
            $this->assertStringNotContainsString('->dateTime()', $contents, $file);
        }
    }

    public function test_project_no_longer_uses_to_datetime_string_for_display(): void
    {
        $files = array_merge(
            $this->phpFilesIn(base_path('app')),
            $this->phpFilesIn(base_path('resources/views'))
        );

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertNotFalse($contents);
            $this->assertStringNotContainsString('toDateTimeString(', $contents, $file);
        }
    }

    /**
     * @return array<int, string>
     */
    private function phpFilesIn(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }
}
