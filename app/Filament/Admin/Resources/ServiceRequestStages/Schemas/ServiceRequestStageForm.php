<?php

namespace App\Filament\Admin\Resources\ServiceRequestStages\Schemas;

use App\Filament\Forms\TranslatableField;
use App\Models\ServiceRequestType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceRequestStageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.type_details'))
                ->columnSpanFull()
                ->schema([
                    Select::make('type_id')
                        ->label(__('admin.labels.type'))
                        ->options(fn () => ServiceRequestType::query()
                            ->active()
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (ServiceRequestType $type) => [
                                $type->id => $type->getTranslatedName(),
                            ])
                        )
                        ->live()
                        ->required(),
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('name', __('admin.labels.name'), true, 'slug'),
                    TextInput::make('slug')
                        ->required()
                        ->unique(
                            ignoreRecord: true,
                            modifyRuleUsing: fn (\Illuminate\Validation\Rules\Unique $rule, \Filament\Schemas\Components\Utilities\Get $get) => $rule->where('type_id', $get('type_id')),
                        )
                        ->maxLength(100),
                    ColorPicker::make('color')
                        ->label(__('admin.labels.color'))
                        ->required(),
                    TextInput::make('sort_order')
                        ->label(__('admin.labels.sort_order'))
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label(__('admin.labels.active'))
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }
}
