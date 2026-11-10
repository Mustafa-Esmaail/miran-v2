<?php

namespace App\Filament\Admin\Resources\AssignmentRules\Schemas;

use App\Enums\AssignmentStrategy;
use App\Models\Employee;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AssignmentRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->label(__('admin.labels.name'))
                ->required()
                ->maxLength(190)
                ->columnSpanFull(),

            Select::make('strategy')
                ->label(__('admin.labels.assignment_strategy'))
                ->options(collect(AssignmentStrategy::cases())->mapWithKeys(
                    fn (AssignmentStrategy $s) => [$s->value => __('admin.options.'.$s->value)]
                ))
                ->required()
                ->default(AssignmentStrategy::LeastLoaded->value),

            TextInput::make('priority')
                ->label(__('admin.labels.priority_order'))
                ->numeric()
                ->default(0)
                ->helperText(__('admin.helpers.assignment_priority')),

            Toggle::make('is_active')
                ->label(__('admin.labels.is_active'))
                ->default(true)
                ->columnSpanFull(),

            Select::make('employee_ids')
                ->label(__('admin.labels.assign_to_employees'))
                ->options(fn () => Employee::query()
                    ->whereNotNull('user_id')
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name', 'id'))
                ->multiple()
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull(),

            Repeater::make('conditions')
                ->label(__('admin.labels.conditions'))
                ->schema([
                    Select::make('field')
                        ->label(__('admin.labels.field'))
                        ->options([
                            'source' => __('admin.labels.source'),
                            'owner_team' => __('admin.labels.owner_team'),
                            'status' => __('admin.labels.status'),
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
        ]);
    }
}
