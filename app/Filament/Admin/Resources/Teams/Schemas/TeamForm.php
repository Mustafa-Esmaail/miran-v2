<?php

namespace App\Filament\Admin\Resources\Teams\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.details'))
                    ->schema([
                        Select::make('department_id')
                            ->label(__('admin.labels.department'))
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label(__('admin.labels.name'))
                            ->required()
                            ->maxLength(190),
                        TextInput::make('code')
                            ->label(__('admin.labels.code'))
                            ->maxLength(100),
                        Toggle::make('is_active')
                            ->label(__('admin.labels.active'))
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
