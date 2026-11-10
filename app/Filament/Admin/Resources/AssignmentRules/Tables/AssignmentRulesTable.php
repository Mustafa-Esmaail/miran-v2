<?php

namespace App\Filament\Admin\Resources\AssignmentRules\Tables;

use App\Models\AssignmentRule;
use App\Models\Employee;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority')
                    ->label(__('admin.labels.priority_order'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('admin.labels.name'))
                    ->searchable(),
                TextColumn::make('strategy')
                    ->label(__('admin.labels.assignment_strategy'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color('info'),
                TextColumn::make('employee_ids')
                    ->label(__('admin.labels.assign_to_employees'))
                    ->formatStateUsing(function (AssignmentRule $record): string {
                        $ids = $record->employee_ids ?? [];
                        if (empty($ids)) {
                            return '—';
                        }
                        $names = Employee::whereIn('id', $ids)->pluck('name');

                        return $names->implode(', ');
                    }),
                TextColumn::make('conditions')
                    ->label(__('admin.labels.conditions'))
                    ->formatStateUsing(fn (AssignmentRule $record): string => count($record->conditions ?? []).' '.(__('admin.labels.conditions')))
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('admin.labels.is_active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                EditAction::make()->label(__('admin.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
                ]),
            ])
            ->defaultSort('priority', 'asc');
    }
}
