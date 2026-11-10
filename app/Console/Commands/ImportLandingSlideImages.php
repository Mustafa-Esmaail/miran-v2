<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Models\Section;
use Database\Seeders\LandingPageSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ImportLandingSlideImages extends Command
{
    protected $signature = 'landing:import-slide-images
        {--apply : Also rewrite already-seeded landing_hero sections in the database}
        {--dry-run : Show what would happen without downloading or writing anything}';

    protected $description = 'Download landing page hero slide images and re-host them on the S3 disk, so a themiran.net domain change cannot break them';

    public function handle(): int
    {
        $slideImages = (new LandingPageSeeder)->slideImages();
        $urls = collect($slideImages)->flatten()->unique()->values();

        if ($urls->isEmpty()) {
            $this->info('No slide images found.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $mapping = [];

        foreach ($urls as $url) {
            $filename = basename(parse_url($url, PHP_URL_PATH) ?? '');
            if ($filename === '') {
                $this->warn("Could not determine filename for [{$url}], skipping.");

                continue;
            }

            $path = 'sections/landing/'.$filename;
            $mapping[$url] = $path;

            if (Storage::disk('s3')->exists($path)) {
                $this->line("Already on S3, skipping download: {$path}");

                continue;
            }

            if ($dryRun) {
                $this->line("[dry-run] Would download {$url} -> {$path}");

                continue;
            }

            $response = Http::timeout(30)->get($url);
            if (! $response->successful()) {
                $this->error("Failed to download [{$url}]: HTTP {$response->status()}");
                unset($mapping[$url]);

                continue;
            }

            Storage::disk('s3')->put($path, $response->body());
            $this->info("Uploaded {$path}");
        }

        if ($dryRun) {
            $this->info('Dry run complete. No files were written.');

            return self::SUCCESS;
        }

        if ($mapping === []) {
            $this->warn('Nothing was uploaded; leaving the seeder and database untouched.');

            return self::FAILURE;
        }

        $this->rewriteSeederFile($mapping);

        if ($this->option('apply')) {
            $this->updateExistingSections($slideImages, $mapping);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $mapping
     */
    private function rewriteSeederFile(array $mapping): void
    {
        $path = base_path('database/seeders/LandingPageSeeder.php');
        $contents = File::get($path);

        foreach ($mapping as $url => $storagePath) {
            $contents = str_replace("'{$url}'", "'{$storagePath}'", $contents);
        }

        File::put($path, $contents);
        $this->info('Updated LandingPageSeeder.php with the new storage paths.');
    }

    /**
     * @param  array<string, array<int, string>>  $slideImages
     * @param  array<string, string>  $mapping
     */
    private function updateExistingSections(array $slideImages, array $mapping): void
    {
        foreach (array_keys($slideImages) as $slug) {
            $page = Page::query()->where('slug', $slug)->first();
            if (! $page) {
                continue;
            }

            $hero = Section::query()
                ->where('page_id', $page->id)
                ->where('type', 'landing_hero')
                ->first();

            if (! $hero) {
                continue;
            }

            $slides = $hero->fieldValue('slides', []);
            if (! is_array($slides) || $slides === []) {
                continue;
            }

            $changed = false;
            $updated = array_map(function (array $slide) use ($mapping, &$changed): array {
                $bg = $slide['background_image'] ?? null;
                if ($bg !== null && isset($mapping[$bg])) {
                    $slide['background_image'] = $mapping[$bg];
                    $changed = true;
                }

                return $slide;
            }, $slides);

            if (! $changed) {
                continue;
            }

            $hero->setField('slides', ['value' => $updated], 'json', 0);
            $this->info("Updated slides for landing page [{$slug}].");
        }
    }
}
