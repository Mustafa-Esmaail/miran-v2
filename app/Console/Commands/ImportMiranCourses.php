<?php

namespace App\Console\Commands;

use App\Services\MiranCourseImporter;
use Database\Seeders\CourseCatalogSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ImportMiranCourses extends Command
{
    protected $signature = 'miran:import-courses
        {--n= : Limit number of courses to import}
        {--seed-catalog : Seed local catalog content (10 courses + groups + lessons)}
        {--seed-only : Seed local catalog content only and skip remote import}';

    protected $description = 'Import courses from themiran.net';

    public function handle(MiranCourseImporter $importer): int
    {
        $limit = $this->option('n');
        $limit = is_numeric($limit) ? (int) $limit : null;
        $seedCatalog = (bool) $this->option('seed-catalog');
        $seedOnly = (bool) $this->option('seed-only');

        if ($seedCatalog || $seedOnly) {
            $this->info('Seeding local course catalog...');
            Artisan::call('db:seed', [
                '--class' => CourseCatalogSeeder::class,
                '--no-interaction' => true,
            ]);
            $this->line(Artisan::output());
            $this->info('Local course catalog seeding completed.');
        }

        if ($seedOnly) {
            return self::SUCCESS;
        }

        $this->info('Starting Miran course import...');

        $results = $importer->import(function (string $status, string $sourceUrl) {
            if ($status === 'failed') {
                $this->warn('Failed: '.$sourceUrl);
            }
        }, $limit);

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
