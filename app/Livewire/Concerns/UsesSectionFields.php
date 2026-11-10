<?php

namespace App\Livewire\Concerns;

use App\Models\Section;

trait UsesSectionFields
{
    public ?int $sectionId = null;

    public array $sectionFields = [];

    private static array $sectionCache = [];

    protected function loadSectionFields(): void
    {
        if (! $this->sectionId) {
            return;
        }

        if (array_key_exists($this->sectionId, self::$sectionCache)) {
            $section = self::$sectionCache[$this->sectionId];
        } else {
            $section = Section::query()
                ->with('sectionFields')
                ->find($this->sectionId);
            self::$sectionCache[$this->sectionId] = $section;
        }

        if (! $section) {
            return;
        }

        $this->sectionFields = $section->sectionFields
            ->mapWithKeys(fn ($field) => [$field->key => $field->value])
            ->all();

        $this->hydrateSectionMeta($section);
    }

    protected function hydrateSectionMeta(Section $section): void {}

    public function fieldValue(string $key, mixed $default = null): mixed
    {
        $value = $this->sectionFields[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        if (is_array($value) && array_key_exists('value', $value)) {
            $raw = $value['value'];

            return $raw === null ? $default : $raw;
        }

        return $value;
    }

    public function fieldText(string $key, string $locale, string $fallback = ''): string
    {
        $value = $this->sectionFields[$key] ?? null;

        if (! is_array($value)) {
            return $fallback;
        }

        if (array_key_exists('value', $value) && ! is_array($value['value'])) {
            $raw = $value['value'];
            if ($raw === null) {
                return $fallback;
            }

            return is_string($raw) ? $raw : (string) $raw;
        }

        $text = $value[$locale] ?? $value['en'] ?? $value['ar'] ?? null;

        if ($text === null) {
            return $fallback;
        }

        return is_string($text) ? $text : (string) $text;
    }
}
