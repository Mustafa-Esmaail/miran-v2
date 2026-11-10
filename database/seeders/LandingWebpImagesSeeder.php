<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\SectionField;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Switches the images of the existing landing pages (Excel, KPI, Power BI,
 * PMP v3) from the bundled PNGs to their WebP copies (public/img/landing-*).
 * Only image paths are touched: a path is replaced only when it still points
 * at a bundled PNG that has a WebP copy, so images uploaded from the admin
 * panel are kept. Safe to run more than once; the old PNGs stay on S3.
 *
 * php artisan db:seed --class=LandingWebpImagesSeeder
 */
class LandingWebpImagesSeeder extends Seeder
{
    protected const PAGE_SLUGS = ['excel', 'kpis-online', 'Power-BI', 'pmp-online-v3'];

    /**
     * Image fields per section type: field key => key of the image inside
     * each list item (null when the item itself is the image path).
     *
     * @var array<string, array<string, string|null>>
     */
    protected const IMAGE_FIELDS = [
        'landing_course_hero' => ['badges' => 'image'],
        'landing_v2_clients' => ['logos' => null],
        'landing_course_benefits' => ['tools' => 'image', 'extras' => 'image'],
    ];

    public function run(): void
    {
        $pages = Page::query()
            ->whereIn('slug', static::PAGE_SLUGS)
            ->with('sections.sectionFields')
            ->get();

        foreach (array_diff(static::PAGE_SLUGS, $pages->pluck('slug')->all()) as $missingSlug) {
            $this->command?->warn("Page [{$missingSlug}] not found — skipped.");
        }

        foreach ($pages as $page) {
            $replaced = 0;

            foreach ($page->sections as $section) {
                foreach (static::IMAGE_FIELDS[$section->type] ?? [] as $fieldKey => $itemImageKey) {
                    $field = $section->sectionFields->firstWhere('key', $fieldKey);

                    if ($field instanceof SectionField) {
                        $replaced += $this->convertField($field, $itemImageKey);
                    }
                }
            }

            $this->command?->info("Page [{$page->slug}]: {$replaced} image(s) switched to WebP.");
        }
    }

    /**
     * Replaces the PNG paths of one list field and returns how many changed.
     */
    protected function convertField(SectionField $field, ?string $itemImageKey): int
    {
        $value = $field->value;
        $items = $value['value'] ?? null;

        if (! is_array($items)) {
            return 0;
        }

        $replaced = 0;

        foreach ($items as $index => $item) {
            $path = $itemImageKey === null ? $item : ($item[$itemImageKey] ?? null);
            $webpKey = $this->webpKey($path);

            if ($webpKey === null) {
                continue;
            }

            if ($itemImageKey === null) {
                $items[$index] = $webpKey;
            } else {
                $items[$index][$itemImageKey] = $webpKey;
            }

            $replaced++;
        }

        if ($replaced > 0) {
            $value['value'] = $items;
            $field->value = $value;
            $field->save();
        }

        return $replaced;
    }

    /**
     * Uploads the WebP copy of a bundled landing PNG and returns its S3 key,
     * or null when the path is not a bundled PNG, has no copy, or the upload fails.
     */
    protected function webpKey(mixed $path): ?string
    {
        if (! is_string($path) || ! preg_match('#^sections/(landing-[a-z0-9-]+)/([A-Za-z0-9_-]+)\.png$#', $path, $matches)) {
            return null;
        }

        [, $directory, $name] = $matches;
        $localPath = public_path("img/{$directory}/{$name}.webp");

        if (! is_file($localPath)) {
            return null;
        }

        $key = "sections/{$directory}/{$name}.webp";

        try {
            $disk = Storage::disk('s3');

            if (! $disk->exists($key)) {
                $disk->put($key, file_get_contents($localPath));
            }

            return $key;
        } catch (Throwable $exception) {
            $this->command?->warn("S3 upload failed for [{$key}], kept the PNG: {$exception->getMessage()}");

            return null;
        }
    }
}
