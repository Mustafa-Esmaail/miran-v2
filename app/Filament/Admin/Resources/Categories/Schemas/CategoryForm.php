<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use App\Filament\Forms\TranslatableField;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.content'))
                ->columnSpanFull()
                ->schema([
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('name', __('admin.fields.name'), true, 'slug'),
                    TextInput::make('slug')
                        ->required()
                        ->helperText(__('admin.helpers.used_in_urls_and_navigation'))
                        ->placeholder(__('admin.placeholders.business_management')),
                ])
                ->columns(2),
            Section::make(__('admin.sections.visibility'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('is_active')
                        ->label(__('admin.labels.active'))
                        ->helperText(__('admin.helpers.control_whether_the_category_appears_across_the_site'))
                        ->default(true),
                ]),
        ]);
    }
}
