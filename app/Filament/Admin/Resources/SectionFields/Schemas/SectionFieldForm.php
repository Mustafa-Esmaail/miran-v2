<?php

namespace App\Filament\Admin\Resources\SectionFields\Schemas;

use App\Filament\Forms\TranslatableField;
use App\Models\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;

class SectionFieldForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            FormSection::make(__('admin.sections.section_field'))
                ->columnSpanFull()
                ->schema([
                    Select::make('section_id')
                        ->label(__('admin.labels.section'))
                        ->relationship('section', 'id')
                        ->getOptionLabelFromRecordUsing(function (Section $record): string {
                            $title = data_get($record->title, app()->getLocale())
                                ?? data_get($record->title, 'en')
                                ?? data_get($record->title, 'ar')
                                ?? $record->type;

                            return "{$record->id} - {$title}";
                        })
                        ->searchable(['id', 'type', 'title->en', 'title->ar'])
                        ->preload()
                        ->required(),
                    TranslatableField::localeSwitcher(),
                    TextInput::make('key')->required()->helperText(__('admin.helpers.unique_identifier_for_this_field')),
                    TextInput::make('type')->default('json')->required(),
                    TextInput::make('group'),
                    TextInput::make('sort_order')->numeric()->default(0)->helperText(__('admin.helpers.lower_numbers_appear_first')),
                    TranslatableField::text('value', 'Value'),
                ])
                ->columns(2),
        ]);
    }
}
