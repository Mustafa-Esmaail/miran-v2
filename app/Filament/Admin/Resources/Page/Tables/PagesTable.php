<?php

namespace App\Filament\Admin\Resources\Page\Tables;

use App\Models\Page;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn (Page $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('slug')->searchable(),
            TextColumn::make('template')->toggleable(),
            TextColumn::make('version')
                ->label(__('admin.landing_versions.labels.version'))
                ->state(fn (Page $record): ?string => $record->isVersioned() ? $record->versionLabel() : null)
                ->badge()
                ->color(fn (Page $record): string => $record->isLiveVersion() ? 'success' : 'gray')
                ->toggleable(),
            TextColumn::make('sections_count')->label(__('admin.labels.sections'))->counts('sections')->sortable(),
            IconColumn::make('is_published')->label(__('admin.labels.published'))->boolean()->toggleable(),
            IconColumn::make('is_active')->label(__('admin.labels.active'))->boolean(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
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
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.pages.heading'))
            ->emptyStateDescription(__('admin.empty_states.pages.description'));
    }
}
