<?php

namespace App\Filament\Admin\Resources\ServiceRequestTypes\Tables;

use App\Models\ServiceRequestType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceRequestTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->label(__('admin.labels.id'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('name')
                ->label(__('admin.labels.name'))
                ->state(fn (ServiceRequestType $record) => $record->getTranslatedName())
                ->searchable(query: fn ($query, $search) => $query->whereRaw(
                    "JSON_EXTRACT(name, '$.ar') LIKE ? OR JSON_EXTRACT(name, '$.en') LIKE ?",
                    ["%{$search}%", "%{$search}%"]
                )),
            TextColumn::make('slug')
                ->label(__('admin.labels.slug'))
                ->searchable(),
            IconColumn::make('sync_to_sheets')
                ->label(__('admin.labels.sync_to_sheets'))
                ->boolean(),
            TextColumn::make('sheet_id')
                ->label(__('admin.labels.sheet_id'))
                ->limit(30)
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
