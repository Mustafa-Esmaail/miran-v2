<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FilamentEnglishFieldsOptionalTest extends TestCase
{
    public function test_direct_english_filament_fields_are_not_required(): void
    {
        foreach (File::allFiles(app_path('Filament')) as $file) {
            $contents = File::get($file->getPathname());

            preg_match_all(
                "/(?:TextInput|Textarea|RichEditor)::make\\('([^']*_en)'\\)/",
                $contents,
                $matches,
                PREG_OFFSET_CAPTURE
            );

            foreach ($matches[1] as [$fieldName, $offset]) {
                $nextFieldOffset = strpos($contents, '::make(', $offset + strlen($fieldName));
                $fieldDefinition = substr(
                    $contents,
                    $offset,
                    $nextFieldOffset === false ? 500 : $nextFieldOffset - $offset
                );

                $this->assertStringNotContainsString(
                    '->required(',
                    $fieldDefinition,
                    sprintf('%s still requires English field [%s].', $file->getRelativePathname(), $fieldName)
                );
            }
        }
    }

    public function test_translatable_field_helper_keeps_english_locale_optional(): void
    {
        $contents = File::get(app_path('Filament/Forms/TranslatableField.php'));

        $textEnglishField = $this->fieldDefinition($contents, 'TextInput::make("{$name}.en")');
        $richEnglishField = $this->fieldDefinition($contents, 'RichEditor::make("{$name}.en")');

        $this->assertStringNotContainsString('->required(', $textEnglishField);
        $this->assertStringNotContainsString('->required(', $richEnglishField);
    }

    private function fieldDefinition(string $contents, string $needle): string
    {
        $offset = strpos($contents, $needle);
        $this->assertNotFalse($offset, sprintf('Could not find [%s].', $needle));

        $nextFieldOffset = strpos($contents, '::make(', $offset + strlen($needle));

        return substr(
            $contents,
            $offset,
            $nextFieldOffset === false ? 500 : $nextFieldOffset - $offset
        );
    }
}
