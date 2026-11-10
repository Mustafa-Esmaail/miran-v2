<?php

namespace App\Filament\Admin\Resources\ServiceRequestStatuses\Schemas;

use App\Enums\ServiceRequestStatusOutcome;
use App\Filament\Forms\TranslatableField;
use App\Models\ServiceRequestStage;
use App\Models\ServiceRequestStatus;
use App\Models\ServiceRequestType;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ServiceRequestStatusForm
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
                    ColorPicker::make('color')
                        ->label(__('admin.labels.color'))
                        ->required(),
                    Select::make('outcome')
                        ->label(__('admin.labels.outcome'))
                        ->options(collect(ServiceRequestStatusOutcome::cases())->mapWithKeys(
                            fn (ServiceRequestStatusOutcome $outcome): array => [$outcome->value => __('admin.options.'.$outcome->value)]
                        ))
                        ->required()
                        ->default(ServiceRequestStatusOutcome::Open->value),
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
                        ->afterStateHydrated(function (Select $component, ?ServiceRequestStatus $record): void {
                            $component->state($record?->stage?->type_id);
                        })
                        ->dehydrated(false)
                        ->live()
                        ->helperText(__('admin.helpers.service_request_status_unassigned_stage')),
                    Select::make('stage_id')
                        ->label(__('admin.labels.stage'))
                        ->options(fn (Get $get) => ServiceRequestStage::query()
                            ->active()
                            ->where('type_id', $get('type_id'))
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (ServiceRequestStage $stage) => [
                                $stage->id => $stage->getTranslatedName(),
                            ])
                        )
                        ->disabled(fn (Get $get): bool => blank($get('type_id'))),
                    TextInput::make('sort_order')
                        ->label(__('admin.labels.sort_order'))
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_active')
                        ->label(__('admin.labels.active'))
                        ->default(true),
                    Toggle::make('is_default')
                        ->label(__('admin.labels.default'))
                        ->helperText(__('admin.helpers.service_request_status_default')),
                    Toggle::make('is_spam')
                        ->label(__('admin.labels.is_spam'))
                        ->helperText(__('admin.helpers.service_request_status_spam')),
                    Toggle::make('requires_date')
                        ->label(__('admin.labels.requires_date'))
                        ->helperText(__('admin.helpers.service_request_status_requires_date')),
                ])
                ->columns(2),
        ]);
    }
}
