<?php

namespace App\Filament\Admin\Resources\CrmTasks\Schemas;

use App\Enums\CrmTaskPriority;
use App\Enums\CrmTaskStatus;
use App\Models\Deal;
use App\Models\Employee;
use App\Models\ServiceRequest;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CrmTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.details'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('admin.labels.title'))
                        ->required()
                        ->maxLength(190)
                        ->columnSpanFull(),
                    Select::make('entity_type')
                        ->label(__('admin.labels.related_to'))
                        ->options([
                            ServiceRequest::class => __('admin.models.service_request'),
                            Deal::class => __('admin.models.deal'),
                        ])
                        ->default(ServiceRequest::class)
                        ->live()
                        ->required(),
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
                    Select::make('priority')
                        ->label(__('admin.labels.priority'))
                        ->options(collect(CrmTaskPriority::cases())->mapWithKeys(
                            fn (CrmTaskPriority $priority) => [$priority->value => __('admin.options.'.$priority->value)]
                        ))
                        ->default(CrmTaskPriority::Normal->value)
                        ->required(),
                    Select::make('status')
                        ->label(__('admin.labels.status'))
                        ->options(collect(CrmTaskStatus::cases())->mapWithKeys(
                            fn (CrmTaskStatus $status) => [$status->value => __('admin.options.'.$status->value)]
                        ))
                        ->default(CrmTaskStatus::Todo->value)
                        ->required(),
                    DateTimePicker::make('due_at')
                        ->label(__('admin.labels.due_at'))
                        ->seconds(false),
                    Select::make('employee_id')
                        ->label(__('admin.labels.assigned_to'))
                        ->options(fn () => Employee::query()
                            ->whereNotNull('user_id')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->default(fn () => Employee::query()->where('user_id', auth()->id())->value('id'))
                        ->searchable()
                        ->preload(),
                    Textarea::make('notes')
                        ->label(__('admin.labels.notes'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
