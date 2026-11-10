<?php

namespace App\Filament\Admin\Resources\ServiceRequestTypes\Schemas;

use App\Filament\Forms\TranslatableField;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceRequestTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.type_details'))
                ->columnSpanFull()
                ->schema([
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('name', __('admin.labels.name'), true, 'slug'),
                    TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(100),
                    TextInput::make('sort_order')
                        ->label(__('admin.labels.sort_order'))
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label(__('admin.labels.active'))
                        ->default(true),
                    Toggle::make('sync_to_sheets')
                        ->label(__('admin.labels.sync_to_sheets'))
                        ->live(),
                    TextInput::make('sheet_id')
                        ->label(__('admin.labels.sheet_id'))
                        ->placeholder('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms')
                        ->visible(fn (callable $get): bool => (bool) $get('sync_to_sheets'))
                        ->required(fn (callable $get): bool => (bool) $get('sync_to_sheets'))
                        ->maxLength(200),
                ])
                ->columns(2),
        ]);
    }
}
