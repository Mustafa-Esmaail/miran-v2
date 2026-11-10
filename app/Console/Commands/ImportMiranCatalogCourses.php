<?php

namespace App\Console\Commands;

use App\Services\MiranCourseImporter;
use Illuminate\Console\Command;

class ImportMiranCatalogCourses extends Command
{
    protected $signature = 'miran:import-catalog-courses {--n= : Limit number of courses to import}';

    protected $description = 'Import courses from themiran.net without syncing CMS pages/sections';

    public function handle(MiranCourseImporter $importer): int
    {
        $this->info('Starting Miran course catalog import...');

        $limit = $this->option('n');
        $limit = is_numeric($limit) ? (int) $limit : null;

        $results = $importer->import(function (string $status, string $sourceUrl) {
            if ($status === 'failed') {
                $this->warn('Failed: '.$sourceUrl);
            }
        }, $limit, false);

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
