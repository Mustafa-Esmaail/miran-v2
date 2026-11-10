<?php

namespace App\Support;

class SectionFieldFormData
{
    public static function fieldsFromForm(array $content): array
    {
        $fields = [];
        $translatable = [];

        foreach ($content as $key => $value) {
            if (! is_string($key)) {
                continue;
            }

            if (self::isTranslatableKey($key) && ! is_array($value)) {
                $baseKey = substr($key, 0, -3);
                $translatable[$baseKey] = true;

                continue;
            }

            $fields[$key] = [
                'type' => self::inferType($value),
                'value' => ['value' => $value],
            ];
        }

        foreach (array_keys($translatable) as $baseKey) {
            $fields[$baseKey] = [
                'type' => 'string',
                'value' => [
                    'en' => $content[$baseKey.'_en'] ?? null,
                    'ar' => $content[$baseKey.'_ar'] ?? null,
                ],
            ];
        }

        return $fields;
    }

    public static function formFromFields(iterable $fields): array
    {
        $content = [];

        foreach ($fields as $field) {
            $key = is_array($field) ? ($field['key'] ?? null) : ($field->key ?? null);
            $value = is_array($field) ? ($field['value'] ?? null) : ($field->value ?? null);

            if (! is_string($key) || ! is_array($value)) {
                continue;
            }

            if (array_key_exists('en', $value) || array_key_exists('ar', $value)) {
                $content[$key.'_en'] = $value['en'] ?? null;
                $content[$key.'_ar'] = $value['ar'] ?? null;

                continue;
            }

            if (array_key_exists('value', $value)) {
                $content[$key] = $value['value'];

                continue;
            }

            $content[$key] = $value;
        }

        return $content;
    }

    private static function isTranslatableKey(string $key): bool
    {
        return str_ends_with($key, '_en') || str_ends_with($key, '_ar');
    }

    private static function inferType(mixed $value): string
    {
        if (is_array($value)) {
            return 'json';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value) || is_float($value)) {
            return 'number';
        }

        return 'string';
    }
}
