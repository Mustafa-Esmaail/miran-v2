<?php

namespace App\Filament\Admin\Resources\BroadcastMails\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BroadcastMailsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('subject')->label(__('admin.fields.subject'))->searchable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function (string $state): string {
                    $key = 'admin.options.'.$state;

                    return __($key) !== $key ? __($key) : $state;
                })
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'sent' => 'success',
                    'sending', 'queued' => 'info',
                    'partially_failed' => 'warning',
                    'failed' => 'danger',
                    default => 'gray',
                })
                ->sortable(),
            TextColumn::make('recipients_count')->label(__('admin.labels.recipients_count'))->counts('recipients'),
            TextColumn::make('creator.name')->label(__('admin.labels.created_by'))->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->actions([
            ViewAction::make()->label(__('admin.pages.view')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.broadcast_mails.heading'))
            ->emptyStateDescription(__('admin.empty_states.broadcast_mails.description'));
    }
}
