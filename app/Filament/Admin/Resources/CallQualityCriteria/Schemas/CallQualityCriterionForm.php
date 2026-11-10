<?php

namespace App\Filament\Admin\Resources\CallQualityCriteria\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CallQualityCriterionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.call_quality.criteria.model'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name.ar')
                        ->label(__('admin.call_quality.criteria.name_ar'))
                        ->required()
                        ->maxLength(255)
                        ->extraInputAttributes(['dir' => 'rtl']),
                    TextInput::make('name.en')
                        ->label(__('admin.call_quality.criteria.name_en'))
                        ->required()
                        ->maxLength(255)
                        ->extraInputAttributes(['dir' => 'ltr']),
                    TextInput::make('weight')
                        ->label(__('admin.call_quality.criteria.weight'))
                        ->helperText(__('admin.call_quality.criteria.weight_helper'))
                        ->numeric()
                        ->step(0.5)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->required()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label(__('admin.call_quality.criteria.is_active'))
                        ->default(true)
                        ->inline(false),
                ]),
        ]);
    }
}
