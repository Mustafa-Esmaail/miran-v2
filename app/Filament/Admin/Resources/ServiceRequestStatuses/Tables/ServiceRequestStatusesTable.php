<?php

namespace App\Filament\Admin\Resources\ServiceRequestStatuses\Tables;

use App\Models\ServiceRequestStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceRequestStatusesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['stage.type']))
            ->columns([
                TextColumn::make('id')
                    ->label(__('admin.labels.id'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.labels.name'))
                    ->state(fn (ServiceRequestStatus $record) => $record->getTranslatedName())
                    ->searchable(query: fn ($query, $search) => $query->whereRaw(
                        "JSON_EXTRACT(name, '$.ar') LIKE ? OR JSON_EXTRACT(name, '$.en') LIKE ?",
                        ["%{$search}%", "%{$search}%"]
                    )),
                TextColumn::make('slug')
                    ->label(__('admin.labels.slug'))
                    ->searchable(),
                ColorColumn::make('color')
                    ->label(__('admin.labels.color')),
                TextColumn::make('outcome')
                    ->label(__('admin.labels.outcome'))
                    ->formatStateUsing(fn ($state) => __('admin.options.'.($state instanceof \BackedEnum ? $state->value : $state)))
                    ->badge()
                    ->color(fn ($state): string => match ($state instanceof \BackedEnum ? $state->value : $state) {
                        'won' => 'success',
                        'lost' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('stage.type.name')
                    ->label(__('admin.labels.type'))
                    ->state(fn (ServiceRequestStatus $record) => $record->stage?->type?->getTranslatedName() ?? __('admin.labels.all'))
                    ->toggleable(),
                TextColumn::make('stage.name')
                    ->label(__('admin.labels.stage'))
                    ->state(fn (ServiceRequestStatus $record) => $record->stage?->getTranslatedName())
                    ->badge()
                    ->color(fn (ServiceRequestStatus $record): string => $record->stage?->color ?? 'gray')
                    ->toggleable(),
                IconColumn::make('is_default')
                    ->label(__('admin.labels.default'))
                    ->boolean(),
                IconColumn::make('is_spam')
                    ->label(__('admin.labels.is_spam'))
                    ->boolean(),
                IconColumn::make('requires_date')
                    ->label(__('admin.labels.requires_date'))
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('admin.labels.active'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('admin.labels.sort_order'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                ViewAction::make()->label(__('admin.pages.view')),
                EditAction::make()->label(__('admin.actions.edit')),
            ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])
            ->groupedBulkActions([
                DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }
}
