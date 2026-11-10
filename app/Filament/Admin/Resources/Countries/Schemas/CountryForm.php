<?php

namespace App\Filament\Admin\Resources\Countries\Schemas;

use App\Filament\Forms\TranslatableField;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.basics'))
                ->columnSpanFull()
                ->schema([
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('name', __('admin.fields.name'), true),
                    TextInput::make('code')
                        ->label(__('admin.fields.country_code'))
                        ->maxLength(10)
                        ->placeholder('SA'),
                    Select::make('status')
                        ->options([
                            'active' => __('admin.options.active'),
                            'inactive' => __('admin.options.inactive'),
                        ])
                        ->required()
                        ->default('active'),
                ])
                ->columns(2),
        ]);
    }
}
