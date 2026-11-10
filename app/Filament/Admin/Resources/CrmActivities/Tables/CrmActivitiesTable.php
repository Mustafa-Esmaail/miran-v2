<?php

namespace App\Filament\Admin\Resources\CrmActivities\Tables;

use App\Models\CrmActivity;
use App\Support\CrmEntityLink;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CrmActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('type')
                    ->label(__('admin.labels.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '-')
                    ->color(fn ($state) => match ($state?->value) {
                        'call' => 'info',
                        'whatsapp' => 'success',
                        'email' => 'primary',
                        'meeting' => 'warning',
                        'visit' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('subject')->label(__('admin.labels.subject'))->searchable()->limit(50),
                TextColumn::make('outcome')
                    ->label(__('admin.labels.outcome'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? __('admin.options.'.$state->value) : '-')
                    ->color(fn ($state) => match ($state?->value) {
                        'interested' => 'success',
                        'reached', 'callback' => 'info',
                        'no_answer' => 'warning',
                        'not_interested' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('entity_id')
                    ->label(__('admin.labels.related_to'))
                    ->formatStateUsing(fn (CrmActivity $record): string => '#'.$record->entity_id)
                    ->url(fn (CrmActivity $record): ?string => CrmEntityLink::editUrl($record->entity_type, $record->entity_id))
                    ->toggleable(),
                TextColumn::make('employee.name')->label(__('admin.labels.logged_by'))->searchable()->toggleable(),
                TextColumn::make('scheduled_at')->label(__('admin.labels.scheduled_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
                TextColumn::make('completed_at')->label(__('admin.labels.completed_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('admin.labels.type'))
                    ->options([
                        'call' => __('admin.options.call'),
                        'email' => __('admin.options.email'),
                        'whatsapp' => __('admin.options.whatsapp'),
                        'meeting' => __('admin.options.meeting'),
                        'note' => __('admin.options.note'),
                        'visit' => __('admin.options.visit'),
                    ])->searchable(),
                SelectFilter::make('outcome')
                    ->label(__('admin.labels.outcome'))
                    ->options([
                        'reached' => __('admin.options.reached'),
                        'no_answer' => __('admin.options.no_answer'),
                        'interested' => __('admin.options.interested'),
                        'not_interested' => __('admin.options.not_interested'),
                        'callback' => __('admin.options.callback'),
                    ])->searchable(),
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
            ->defaultSort('created_at', 'desc');
    }
}
