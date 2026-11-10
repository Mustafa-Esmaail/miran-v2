<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

class TranslatableField
{
    public static function localeSwitcher(string $statePath = 'form_locale'): ToggleButtons
    {
        return ToggleButtons::make($statePath)
            ->label(__('admin.language.label'))
            ->options([
                'ar' => __('admin.options.ar'),
                'en' => __('admin.options.en'),
            ])
            ->default(fn () => app()->getLocale())
            ->afterStateHydrated(function (Set $set, ?string $state) use ($statePath) {
                if (! $state) {
                    $set($statePath, app()->getLocale());
                }
            })
            ->inline()
            ->live()
            ->dehydrated(false)
            ->columnSpanFull();
    }

    public static function text(
        string $name,
        ?string $label = null,
        bool $required = false,
        ?string $autoSlugField = null
    ): Group {
        return Group::make([
            TextInput::make("{$name}.ar")
                ->label($label)
                ->required($required)
                ->live(onBlur: $autoSlugField !== null)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) use ($autoSlugField): void {
                    self::syncSlugFromText($get, $set, $state, $old, $autoSlugField);
                })
                ->visible(fn (Get $get) => $get('form_locale') === 'ar' || $get('form_locale') === null),
            TextInput::make("{$name}.en")
                ->label($label)
                ->live(onBlur: $autoSlugField !== null)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old) use ($autoSlugField): void {
                    self::syncSlugFromText($get, $set, $state, $old, $autoSlugField);
                })
                ->visible(fn (Get $get) => $get('form_locale') === 'en' || $get('form_locale') === null),
        ])->columnSpanFull();
    }

    public static function textarea(
        string $name,
        ?string $label = null,
        bool $required = false
    ): Group {
        return Group::make([
            Textarea::make("{$name}.ar")
                ->label($label)
                ->required($required)
                ->visible(fn (Get $get) => $get('form_locale') === 'ar' || $get('form_locale') === null),
            Textarea::make("{$name}.en")
                ->label($label)
                ->visible(fn (Get $get) => $get('form_locale') === 'en' || $get('form_locale') === null),
        ])->columnSpanFull();
    }

    public static function rich(string $name, ?string $label = null, bool $required = false): Group
    {
        return Group::make([
            RichEditor::make("{$name}.ar")
                ->label($label)
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('course-intros')
                ->fileAttachmentsVisibility('private')
                ->required($required)
                ->visible(fn (Get $get) => $get('form_locale') === 'ar' || $get('form_locale') === null),
            RichEditor::make("{$name}.en")
                ->label($label)
                ->fileAttachmentsDisk('s3')
                ->fileAttachmentsDirectory('course-intros')
                ->fileAttachmentsVisibility('private')
                ->visible(fn (Get $get) => $get('form_locale') === 'en' || $get('form_locale') === null),
        ])->columnSpanFull();
    }

    protected static function syncSlugFromText(
        Get $get,
        Set $set,
        ?string $state,
        ?string $old,
        ?string $autoSlugField
    ): void {
        if ($autoSlugField === null || blank($state)) {
            return;
        }

        $currentSlug = (string) $get($autoSlugField);
        $oldSlug = Str::slug((string) $old);

        if (filled($currentSlug) && $currentSlug !== $oldSlug) {
            return;
        }

        $set($autoSlugField, Str::slug($state));
    }
}
