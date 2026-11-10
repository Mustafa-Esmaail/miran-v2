<?php

namespace App\Filament\Admin\Resources\Bundles\Schemas;

use App\Filament\Forms\TranslatableField;
use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BundleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.content'))
                ->columnSpanFull()
                ->schema([
                    Select::make('category_id')
                        ->relationship('category', 'slug')
                        ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->getTranslation('name', app()->getLocale()))
                        ->label(__('admin.labels.category'))
                        ->required()
                        ->preload()
                        ->searchable(['name->en', 'name->ar', 'slug']),
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true, 'slug'),
                    TranslatableField::rich('summary', __('admin.fields.summary'))->columnSpanFull(),
                    TranslatableField::rich('description', __('admin.fields.description'))->columnSpanFull(),
                ]),
            Section::make(__('admin.sections.publishing'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('slug')
                        ->required()
                        ->helperText(__('admin.helpers.used_in_urls_and_internal_links'))
                        ->placeholder(__('admin.placeholders.leadership_bundle')),
                    Toggle::make('is_active')
                        ->label(__('admin.labels.active'))
                        ->helperText(__('admin.helpers.control_whether_the_bundle_appears_in_listings'))
                        ->default(true),
                ])
                ->columns(2),
            Section::make(__('admin.sections.media'))
                ->columnSpanFull()
                ->schema([
                    FileUpload::make('image_path')
                        ->disk('s3')
                        ->directory('bundle-thumbnails')
                        ->visibility('private')
                        ->image()
                        ->helperText(__('admin.helpers.recommended_size_1200x630')),
                ])
                ->columns(2),
            Section::make(__('admin.sections.pricing'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('price')
                        ->numeric()
                        ->label(__('admin.labels.price'))
                        ->prefix('SAR'),
                    Select::make('discounts')
                        ->multiple()
                        ->relationship('discounts', 'type')
                        ->getOptionLabelFromRecordUsing(fn ($record) => sprintf(
                            '%s (%s)',
                            ucfirst($record->type),
                            $record->amount
                        ))
                        ->preload()
                        ->searchable()
                        ->label(__('admin.labels.discounts'))
                        ->placeholder(__('admin.placeholders.select_discounts')),
                ])
                ->columns(2),
        ]);
    }
}
