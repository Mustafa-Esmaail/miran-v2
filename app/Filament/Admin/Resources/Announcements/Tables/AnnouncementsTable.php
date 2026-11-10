<?php

namespace App\Filament\Admin\Resources\Announcements\Tables;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnnouncementsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn (Announcement $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })->sortable(),
            TextColumn::make('starts_at')->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('ends_at')->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->actions([
            Action::make('publish')
                ->label(__('admin.labels.publish'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (Announcement $record): bool => $record->status !== 'published')
                ->action(function (Announcement $record, AnnouncementService $service) {
                    $service->publish($record);
                })
                ->requiresConfirmation(),
            Action::make('unpublish')
                ->label(__('admin.labels.unpublish'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Announcement $record): bool => $record->status === 'published')
                ->action(function (Announcement $record, AnnouncementService $service) {
                    $service->unpublish($record);
                })
                ->requiresConfirmation(),
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.announcements.heading'))
            ->emptyStateDescription(__('admin.empty_states.announcements.description'));
    }
}
