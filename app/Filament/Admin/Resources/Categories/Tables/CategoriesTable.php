<?php

namespace App\Filament\Admin\Resources\Categories\Tables;

use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable(),
            TextColumn::make('name')
                ->label(__('admin.labels.name'))
                ->state(fn (Category $record) => $record->getTranslation('name', app()->getLocale()))
                ->searchable(),
            TextColumn::make('name_ar')
                ->label(__('admin.labels.name_ar'))
                ->state(fn (Category $record) => $record->getTranslation('name', 'ar'))
                ->toggleable(isToggledHiddenByDefault: true)->searchable(),
            TextColumn::make('name_en')
                ->label(__('admin.labels.name_en'))
                ->state(fn (Category $record) => $record->getTranslation('name', 'en'))
                ->toggleable(isToggledHiddenByDefault: true)->searchable(),
            TextColumn::make('slug')->label(__('admin.labels.slug'))->searchable()->toggleable(),
            TextColumn::make('courses_count')->label(__('admin.labels.courses'))->counts('courses')->sortable()->badge(),
            IconColumn::make('is_active')->label(__('admin.labels.active'))->boolean(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            TernaryFilter::make('is_active')
                ->label(__('admin.labels.active'))
                ->trueLabel(__('admin.options.active'))
                ->falseLabel(__('admin.options.inactive')),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.categories.heading'))
            ->emptyStateDescription(__('admin.empty_states.categories.description'));
    }
}
