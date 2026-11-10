<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\Bundle;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\CourseAttachment;
use App\Models\CourseGroup;
use App\Models\Lesson;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\SectionField;
use Illuminate\Console\Command;

class ContentFixEncodingCommand extends Command
{
    protected $signature = 'content:fix-encoding {--dry-run : Show changes without saving}';

    protected $description = 'Fix mojibake (latin1) text stored in content fields.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $models = $this->fixTranslatableModels($dryRun);
        $sections = $this->fixSections($dryRun);

        $this->info("Translatable models updated: {$models}");
        $this->info("Sections updated: {$sections}");

        if ($dryRun) {
            $this->info('Dry run completed. No changes were saved.');
        }

        return self::SUCCESS;
    }

    private function fixTranslatableModels(bool $dryRun): int
    {
        $updated = 0;

        foreach ($this->translatableModels() as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }

            $model = new $modelClass;

            if (! method_exists($model, 'getTranslatableAttributes')) {
                continue;
            }

            $fields = $model->getTranslatableAttributes();

            if ($fields === []) {
                continue;
            }

            $modelClass::query()
                ->select(array_merge(['id'], $fields))
                ->chunkById(100, function ($records) use ($dryRun, &$updated, $fields) {
                    foreach ($records as $record) {
                        $dirty = false;

                        foreach ($fields as $field) {
                            $original = $record->getTranslations($field);
                            $fixed = $this->fixTranslatable($original);

                            if ($original !== $fixed) {
                                $record->setTranslations($field, $fixed);
                                $dirty = true;
                            }
                        }

                        if ($dirty) {
                            $updated++;
                            if (! $dryRun) {
                                $record->save();
                            }
                        }
                    }
                });
        }

        return $updated;
    }

    private function fixSections(bool $dryRun): int
    {
        $updated = 0;

        SectionField::query()
            ->select(['id', 'value'])
            ->chunkById(200, function ($fields) use ($dryRun, &$updated) {
                foreach ($fields as $field) {
                    $original = is_array($field->value) ? $field->value : [];
                    $fixed = $this->fixContentArray($original);

                    if ($original !== $fixed) {
                        $updated++;
                        if (! $dryRun) {
                            $field->value = $fixed;
                            $field->save();
                        }
                    }
                }
            });

        return $updated;
    }

    private function fixTranslatable(array $translations): array
    {
        $fixed = [];

        foreach ($translations as $locale => $value) {
            $fixed[$locale] = is_string($value) ? $this->fixString($value) : $value;
        }

        return $fixed;
    }

    private function fixContentArray(array $content): array
    {
        $fixed = [];

        foreach ($content as $key => $value) {
            if (is_array($value)) {
                $fixed[$key] = $this->fixContentArray($value);

                continue;
            }

            $fixed[$key] = is_string($value) ? $this->fixString($value) : $value;
        }

        return $fixed;
    }

    private function fixString(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        if (! preg_match('/[ØÙÃÂ]/', $value)) {
            return $value;
        }

        return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }

    private function translatableModels(): array
    {
        return [
            Announcement::class,
            Blog::class,
            BlogCategory::class,
            BlogComment::class,
            Bundle::class,
            Category::class,
            Course::class,
            CourseAttachment::class,
            CourseGroup::class,
            Coupon::class,
            Lesson::class,
            MenuItem::class,
            Page::class,
        ];
    }
}
