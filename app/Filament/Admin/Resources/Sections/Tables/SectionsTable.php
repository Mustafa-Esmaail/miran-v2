<?php

namespace App\Filament\Admin\Resources\Sections\Tables;

use App\Models\Page;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('page.title')
                ->label(__('admin.labels.page'))
                ->formatStateUsing(fn ($state, $record) => $record->page?->getTranslation('title', app()->getLocale()) ?? '-'),
            TextColumn::make('type')->badge(),
            TextColumn::make('title'),
            TextColumn::make('subtitle')->limit(30)->toggleable(),
            IconColumn::make('is_active')->label(__('admin.labels.active'))->boolean(),
            TextColumn::make('sort_order')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
        ])->filters([
            SelectFilter::make('page_id')
                ->label(__('admin.labels.page'))
                ->options(Page::query()->get()->mapWithKeys(function (Page $page) {
                    return [$page->id => $page->getTranslation('title', app()->getLocale())];
                }))->searchable(),
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
            ->defaultSort('sort_order')
            ->emptyStateHeading(__('admin.empty_states.sections.heading'))
            ->emptyStateDescription(__('admin.empty_states.sections.description'));
    }
}
