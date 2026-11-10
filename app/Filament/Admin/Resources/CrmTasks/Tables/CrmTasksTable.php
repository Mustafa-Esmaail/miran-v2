<?php

namespace App\Filament\Admin\Resources\CrmTasks\Tables;

use App\Enums\CrmTaskStatus;
use App\Models\CrmTask;
use App\Support\CrmEntityLink;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('title')->label(__('admin.labels.title'))->searchable()->limit(60),
                TextColumn::make('priority')
                    ->label(__('admin.labels.priority'))
                    ->badge()
                    ->headerFilter(
                        SelectFilter::make('priority')
                            ->options([
                                'urgent' => __('admin.options.urgent'),
                                'high' => __('admin.options.high'),
                                'normal' => __('admin.options.normal'),
                                'low' => __('admin.options.low'),
                            ])
                            ->searchable()
                            ->native(false)
                    )
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '-')
                    ->color(fn ($state) => match ($state?->value) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'normal' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->badge()
                    ->headerFilter(
                        SelectFilter::make('status')
                            ->options([
                                CrmTaskStatus::Todo->value => __('admin.options.'.CrmTaskStatus::Todo->value),
                                CrmTaskStatus::InProgress->value => __('admin.options.'.CrmTaskStatus::InProgress->value),
                                CrmTaskStatus::Done->value => __('admin.options.'.CrmTaskStatus::Done->value),
                            ])
                            ->searchable()
                            ->native(false)
                    )
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '-')
                    ->color(fn ($state) => match ($state?->value) {
                        'todo' => 'gray',
                        'in_progress' => 'warning',
                        'done' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('due_at')
                    ->label(__('admin.labels.due_at'))
                    ->dateTime('Y-m-d H:i')
                    ->color(fn (CrmTask $record): string => ! $record->is_done && $record->due_at?->isPast() ? 'danger' : 'gray')
                    ->sortable(),
                IconColumn::make('is_done')
                    ->label(__('admin.labels.is_done'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('entity_id')
                    ->label(__('admin.labels.related_to'))
                    ->formatStateUsing(fn (CrmTask $record): string => '#'.$record->entity_id)
                    ->url(fn (CrmTask $record): ?string => CrmEntityLink::editUrl($record->entity_type, $record->entity_id))
                    ->toggleable(),
                TextColumn::make('employee.name')
                    ->label(__('admin.labels.assigned_to'))
                    ->searchable()
                    ->toggleable()
                    ->headerFilter(
                        SelectFilter::make('employee_id')
                            ->relationship('employee', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                    ),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                Action::make('mark_done')
                    ->label(__('admin.actions.mark_done'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CrmTask $record) => ! $record->is_done)
                    ->action(fn (CrmTask $record) => $record->update([
                        'status' => CrmTaskStatus::Done,
                        'is_done' => true,
                        'completed_at' => now(),
                    ])),
                EditAction::make()->label(__('admin.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
                ]),
            ])
            ->defaultSort('due_at', 'asc');
    }
}
