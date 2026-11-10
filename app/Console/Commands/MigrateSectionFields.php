<?php

namespace App\Console\Commands;

use App\Models\Section;
use App\Models\SectionField;
use App\Services\SectionFieldNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class MigrateSectionFields extends Command
{
    protected $signature = 'cms:migrate-sections';

    protected $description = 'Normalize section content into section_fields.';

    public function handle(): int
    {
        if (! Schema::hasColumn('sections', 'content')) {
            $this->info('sections.content column not found. Nothing to migrate.');

            return self::SUCCESS;
        }

        $sectionsProcessed = 0;
        $fieldsUpserted = 0;

        Section::query()
            ->select(['id', 'content'])
            ->chunkById(200, function ($sections) use (&$sectionsProcessed, &$fieldsUpserted) {
                foreach ($sections as $section) {
                    $sectionsProcessed++;

                    $content = $this->decodeContent($section->getRawOriginal('content'));
                    $fields = SectionFieldNormalizer::extractFields($content);

                    foreach ($fields as $key => $payload) {
                        SectionField::updateOrCreate(
                            [
                                'section_id' => $section->id,
                                'key' => $key,
                            ],
                            [
                                'type' => $payload['type'],
                                'value' => $payload['value'],
                                'group' => null,
                                'sort_order' => 0,
                            ]
                        );

                        $fieldsUpserted++;
                    }
                }
            });

        $this->info("Sections processed: {$sectionsProcessed}");
        $this->info("Fields upserted: {$fieldsUpserted}");

        return self::SUCCESS;
    }

    private function decodeContent(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
