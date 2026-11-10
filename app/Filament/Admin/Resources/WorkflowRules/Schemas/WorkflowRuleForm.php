<?php

namespace App\Filament\Admin\Resources\WorkflowRules\Schemas;

use App\Enums\CrmTaskPriority;
use App\Enums\WorkflowActionType;
use App\Enums\WorkflowTrigger;
use App\Models\Employee;
use App\Models\ServiceRequestStatus;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WorkflowRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label(__('admin.labels.name'))
                ->required()
                ->maxLength(190)
                ->columnSpanFull(),

            Select::make('trigger_event')
                ->label(__('admin.labels.trigger_event'))
                ->options(collect(WorkflowTrigger::cases())->mapWithKeys(
                    fn (WorkflowTrigger $t) => [$t->value => __('admin.options.'.$t->value)]
                ))
                ->required(),

            Toggle::make('is_active')
                ->label(__('admin.labels.is_active'))
                ->default(true),

            Repeater::make('conditions')
                ->label(__('admin.labels.conditions'))
                ->schema([
                    Select::make('field')
                        ->label(__('admin.labels.field'))
                        ->options([
                            'source' => __('admin.labels.source'),
                            'owner_team' => __('admin.labels.owner_team'),
                            'status' => __('admin.labels.status'),
                            'new_status' => __('admin.labels.new_status'),
                            'stage' => __('admin.labels.stage'),
                            'new_stage' => __('admin.labels.new_stage'),
                        ])
                        ->required(),

                    Select::make('operator')
                        ->label(__('admin.labels.operator'))
                        ->options([
                            'equals' => __('admin.options.equals'),
                            'not_equals' => __('admin.options.not_equals'),
                        ])
                        ->required()
                        ->default('equals'),

                    TextInput::make('value')
                        ->label(__('admin.labels.value'))
                        ->required(),
                ])
                ->columns(3)
                ->addActionLabel(__('admin.actions.add_condition'))
                ->collapsible()
                ->helperText(__('admin.helpers.leave_conditions_empty'))
                ->columnSpanFull(),

            Repeater::make('actions')
                ->label(__('admin.labels.actions'))
                ->schema([
                    Select::make('type')
                        ->label(__('admin.labels.action_type'))
                        ->options(collect(WorkflowActionType::cases())->mapWithKeys(
                            fn (WorkflowActionType $a) => [$a->value => __('admin.options.'.$a->value)]
                        ))
                        ->required()
                        ->live(),

                    TextInput::make('title')
                        ->label(__('admin.labels.task_title'))
                        ->visible(fn ($get) => $get('type') === WorkflowActionType::CreateTask->value),

                    Select::make('priority')
                        ->label(__('admin.labels.priority'))
                        ->options(collect(CrmTaskPriority::cases())->mapWithKeys(
                            fn (CrmTaskPriority $p) => [$p->value => __('admin.options.'.$p->value)]
                        ))
                        ->visible(fn ($get) => $get('type') === WorkflowActionType::CreateTask->value)
                        ->default(CrmTaskPriority::Normal->value),

                    TextInput::make('due_in_hours')
                        ->label(__('admin.labels.due_in_hours'))
                        ->numeric()
                        ->suffix(__('admin.labels.hours'))
                        ->default(24)
                        ->visible(fn ($get) => $get('type') === WorkflowActionType::CreateTask->value),

                    Select::make('employee_id')
                        ->label(__('admin.labels.assign_task_to'))
                        ->options(fn () => Employee::query()
                            ->whereNotNull('user_id')
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->nullable()
                        ->visible(fn ($get) => in_array($get('type'), [
                            WorkflowActionType::CreateTask->value,
                            WorkflowActionType::AssignEmployee->value,
                        ])),

                    Select::make('value')
                        ->label(__('admin.labels.new_status'))
                        ->options(fn () => ServiceRequestStatus::query()
                            ->active()
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (ServiceRequestStatus $status) => [$status->slug => $status->getTranslatedName()])
                        )
                        ->visible(fn ($get) => $get('type') === WorkflowActionType::UpdateStatus->value),
                ])
                ->columns(2)
                ->addActionLabel(__('admin.actions.add_action'))
                ->collapsible()
                ->required()
                ->columnSpanFull(),
        ]);
    }
}
