<?php

namespace App\Console\Commands;

use App\Services\MiranCourseImporter;
use Illuminate\Console\Command;

class ImportMiranCoursesFromCsv extends Command
{
    protected $signature = 'miran:import-courses-csv
        {--file= : Absolute or relative CSV file path}';

    protected $description = 'Import courses from CSV and auto-seed LMS defaults (groups, lessons, attachments, quizzes)';

    public function handle(MiranCourseImporter $importer): int
    {
        $file = (string) ($this->option('file') ?: base_path('Miran courses Sheet.csv'));
        if (! str_starts_with($file, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:\\\\/', $file)) {
            $file = base_path($file);
        }

        $this->info('Starting Miran CSV course import...');
        $this->line('File: '.$file);

        try {
            $results = $importer->importFromCsv($file, function (string $status, string $context) {
                if ($status === 'failed') {
                    $this->warn('Failed: '.$context);
                }
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Import complete. Total: %d, Created: %d, Updated: %d, Failed: %d',
            $results['total'],
            $results['created'],
            $results['updated'],
            $results['failed']
        ));

        return self::SUCCESS;
    }
}
