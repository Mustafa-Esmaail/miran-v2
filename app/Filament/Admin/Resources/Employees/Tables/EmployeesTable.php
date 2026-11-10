<?php

namespace App\Filament\Admin\Resources\Employees\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('employee_code')->label(__('admin.labels.employee_code'))->searchable()->copyable(),
                TextColumn::make('name')->label(__('admin.labels.name'))->searchable()->sortable(),
                TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable()->toggleable(),
                TextColumn::make('email')->label(__('admin.labels.email'))->searchable()->toggleable(),
                TextColumn::make('phone')->label(__('admin.labels.phone'))->searchable()->toggleable(),
                TextColumn::make('job_title')->label(__('admin.labels.job_title'))->toggleable(),
                TextColumn::make('role')
                    ->label(__('admin.labels.role'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '—')
                    ->color(fn ($state) => match ($state?->value) {
                        'sales_manager' => 'danger',
                        'department_manager' => 'primary',
                        'team_lead' => 'warning',
                        'sales_person' => 'info',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('commission_rate')
                    ->label(__('admin.labels.commission_rate'))
                    ->suffix('%')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('department.name')->label(__('admin.labels.department'))->toggleable()->sortable(),
                TextColumn::make('team.name')->label(__('admin.labels.team'))->toggleable()->sortable(),
                IconColumn::make('is_active')->label(__('admin.labels.active'))->boolean()->sortable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                EditAction::make()->label(__('admin.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
