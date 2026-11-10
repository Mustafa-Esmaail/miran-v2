<?php

namespace App\Filament\Admin\Resources\CrmActivities\Schemas;

use App\Enums\CrmActivityOutcome;
use App\Enums\CrmActivityType;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\ServiceRequest;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CrmActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.details'))
                ->schema([
                    Select::make('entity_type')
                        ->label(__('admin.labels.related_to'))
                        ->options([
                            ServiceRequest::class => __('admin.models.service_request'),
                            Deal::class => __('admin.models.deal'),
                        ])
                        ->default(ServiceRequest::class)
                        ->required()
                        ->live(),
                    Select::make('entity_id')
                        ->label(__('admin.labels.related_to'))
                        ->options(fn ($get) => $get('entity_type') === Deal::class
                            ? Deal::query()
                                ->orderByDesc('id')
                                ->limit(100)
                                ->get()
                                ->mapWithKeys(fn (Deal $deal) => [
                                    $deal->id => '#'.$deal->id.' - '.$deal->title,
                                ])
                            : ServiceRequest::query()
                                ->orderByDesc('id')
                                ->limit(100)
                                ->get()
                                ->mapWithKeys(fn (ServiceRequest $request) => [
                                    $request->id => '#'.$request->id.' - '.($request->name ?: $request->phone),
                                ]))
                        ->searchable()
                        ->required(),
                    Select::make('type')
                        ->label(__('admin.labels.type'))
                        ->options(collect(CrmActivityType::cases())->mapWithKeys(
                            fn (CrmActivityType $type) => [$type->value => __('admin.options.'.$type->value)]
                        ))
                        ->required(),
                    Select::make('outcome')
                        ->label(__('admin.labels.outcome'))
                        ->options(collect(CrmActivityOutcome::cases())->mapWithKeys(
                            fn (CrmActivityOutcome $outcome) => [$outcome->value => __('admin.options.'.$outcome->value)]
                        ))
                        ->nullable(),
                    TextInput::make('subject')
                        ->label(__('admin.labels.subject'))
                        ->required()
                        ->maxLength(190)
                        ->columnSpanFull(),
                    Textarea::make('body')
                        ->label(__('admin.labels.body'))
                        ->rows(4)
                        ->columnSpanFull(),
                    DateTimePicker::make('scheduled_at')
                        ->label(__('admin.labels.scheduled_at'))
                        ->seconds(false),
                    DateTimePicker::make('completed_at')
                        ->label(__('admin.labels.completed_at'))
                        ->seconds(false),
                    Select::make('employee_id')
                        ->label(__('admin.labels.logged_by'))
                        ->options(fn () => Employee::query()
                            ->whereNotNull('user_id')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->default(fn () => Employee::query()->where('user_id', auth()->id())->value('id'))
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
        ]);
    }
}
