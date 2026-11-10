<?php

namespace App\Filament\Admin\Resources\BlogCategories\Schemas;

use App\Enums\BlogCategoryStatus;
use App\Filament\Forms\TranslatableField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BlogCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.category_details'))
                ->columnSpanFull()
                ->schema([
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true, 'slug'),
                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText(__('admin.helpers.used_in_urls_and_filters'))
                        ->placeholder(__('admin.placeholders.leadership')),
                    Select::make('status')
                        ->options([
                            BlogCategoryStatus::Active->value => __('admin.options.active'),
                            BlogCategoryStatus::Inactive->value => __('admin.options.inactive'),
                        ])
                        ->required()
                        ->default(BlogCategoryStatus::Active->value),
                ])
                ->columns(2),
        ]);
    }
}
