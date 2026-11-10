<?php

namespace App\Filament\Admin\Resources\Bundles\Tables;

use App\Models\Bundle;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BundlesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn (Bundle $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('slug')->searchable()->toggleable(),
            TextColumn::make('category.name')
                ->label(__('admin.labels.category'))
                ->formatStateUsing(fn ($state, Bundle $record) => $record->category?->getTranslation('name', app()->getLocale()) ?? '-')
                ->sortable(),
            TextColumn::make('price')->label(__('admin.labels.price'))->money('SAR')->sortable(),
            TextColumn::make('final_price')
                ->label(__('admin.labels.final_price'))
                ->state(fn (Bundle $record) => $record->final_price)
                ->money('SAR')
                ->sortable(),
            TextColumn::make('courses_count')->counts('courses')->label(__('admin.labels.courses'))->sortable(),
            TextColumn::make('is_active')
                ->label(__('admin.labels.active'))
                ->badge()
                ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('category_id')
                ->relationship('category', 'slug')
                ->getOptionLabelFromRecordUsing(fn (Category $record): string => $record->getTranslation('name', app()->getLocale()))
                ->label(__('admin.labels.category'))
                ->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk'))->authorizeIndividualRecords('delete'),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.bundles.heading'))
            ->emptyStateDescription(__('admin.empty_states.bundles.description'));
    }
}
