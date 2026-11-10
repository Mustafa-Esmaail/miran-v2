<?php

namespace App\Filament\Admin\Resources\NelcSyncLogs\Tables;

use App\Enums\NelcSyncStatus;
use App\Models\NelcSyncLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NelcSyncLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('event_type')->label(__('admin.labels.event_type'))->badge()->sortable(),
                TextColumn::make('user.name')->label(__('admin.labels.trainee'))->searchable(),
                TextColumn::make('course.slug')->label(__('admin.labels.course'))->searchable(),
                TextColumn::make('courseGroup.title')
                    ->label(__('admin.labels.group'))
                    ->formatStateUsing(fn ($state, NelcSyncLog $record) => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.labels.status'))
                    ->formatStateUsing(function ($state): string {
                        $value = $state instanceof \BackedEnum ? $state->value : (string) $state;
                        $key = 'admin.options.'.$value;

                        return __($key) !== $key ? __($key) : $value;
                    })
                    ->color(fn ($state): string => match ($state instanceof NelcSyncStatus ? $state : NelcSyncStatus::from((string) $state)) {
                        NelcSyncStatus::Sent => 'success',
                        NelcSyncStatus::Failed => 'danger',
                        NelcSyncStatus::Pending => 'warning',
                    })
                    ->badge()
                    ->sortable(),
                TextColumn::make('attempts')->label(__('admin.labels.attempts'))->sortable()->toggleable(),
                TextColumn::make('response')->label(__('admin.labels.response'))->limit(60)->toggleable()->wrap(),
                TextColumn::make('synced_at')->label(__('admin.labels.synced_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->options([
                        'Registered' => 'Registered',
                        'Initialized' => 'Initialized',
                        'Attempted' => 'Attempted',
                        'Earned' => 'Earned',
                        'Rated' => 'Rated',
                    ])
                    ->native(false),
                SelectFilter::make('status')
                    ->options(collect(NelcSyncStatus::cases())->mapWithKeys(
                        fn (NelcSyncStatus $status): array => [$status->value => __('admin.options.'.$status->value)]
                    ))
                    ->native(false),
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.nelc_sync_logs.heading'))
            ->emptyStateDescription(__('admin.empty_states.nelc_sync_logs.description'));
    }
}
