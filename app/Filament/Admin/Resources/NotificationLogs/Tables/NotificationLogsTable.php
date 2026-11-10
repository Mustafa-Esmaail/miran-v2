<?php

namespace App\Filament\Admin\Resources\NotificationLogs\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NotificationLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->toggleable(),
            TextColumn::make('type')->label(__('admin.labels.type'))->formatStateUsing(fn (string $state) => class_basename($state)),
            TextColumn::make('notifiable_type')->label(__('admin.labels.notifiable'))->formatStateUsing(fn (string $state) => class_basename($state)),
            TextColumn::make('notifiable_id')->label(__('admin.labels.notifiable_id')),
            TextColumn::make('read_at')->label(__('admin.labels.read_at'))->dateTime('Y-m-d H:i')->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit'))->visible(false),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.notification_logs.heading'))
            ->emptyStateDescription(__('admin.empty_states.notification_logs.description'));
    }
}
