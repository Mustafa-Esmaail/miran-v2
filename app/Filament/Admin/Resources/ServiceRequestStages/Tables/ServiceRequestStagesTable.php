<?php

namespace App\Filament\Admin\Resources\ServiceRequestStages\Tables;

use App\Models\ServiceRequestStage;
use App\Models\ServiceRequestType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceRequestStagesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')
                ->label(__('admin.labels.id'))
                ->sortable()
                ->toggleable(),
            TextColumn::make('type.name')
                ->label(__('admin.labels.type'))
                ->state(fn (ServiceRequestStage $record) => $record->type?->getTranslatedName())
                ->sortable(),
            TextColumn::make('name')
                ->label(__('admin.labels.name'))
                ->state(fn (ServiceRequestStage $record) => $record->getTranslatedName())
                ->searchable(query: fn ($query, $search) => $query->whereRaw(
                    "JSON_EXTRACT(name, '$.ar') LIKE ? OR JSON_EXTRACT(name, '$.en') LIKE ?",
                    ["%{$search}%", "%{$search}%"]
                )),
            TextColumn::make('slug')
                ->label(__('admin.labels.slug'))
                ->searchable(),
            ColorColumn::make('color')
                ->label(__('admin.labels.color')),
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
            ->filters([
                SelectFilter::make('type_id')
                    ->label(__('admin.labels.type'))
                    ->options(fn () => ServiceRequestType::query()
                        ->active()
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (ServiceRequestType $type) => [
                            $type->id => $type->getTranslatedName(),
                        ])
                    ),
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
