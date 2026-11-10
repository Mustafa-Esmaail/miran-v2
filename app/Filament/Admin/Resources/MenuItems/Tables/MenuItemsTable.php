<?php

namespace App\Filament\Admin\Resources\MenuItems\Tables;

use App\Models\Menu;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MenuItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('menu.name')->label(__('admin.labels.menu'))->searchable(),
            TextColumn::make('label')
                ->label(__('admin.fields.label'))
                ->formatStateUsing(fn ($state, $record) => $record->getTranslation('label', app()->getLocale()))
                ->searchable(),
            TextColumn::make('type')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'page' => 'primary',
                    'custom' => 'info',
                    default => 'secondary',
                })
                ->sortable(),
            TextColumn::make('page.slug')->label(__('admin.labels.page'))->toggleable(),
            TextColumn::make('url')->label(__('admin.labels.url'))->limit(30)->toggleable(),
            TextColumn::make('parent.label')
                ->label(__('admin.labels.parent'))
                ->formatStateUsing(fn ($state, $record) => $record->parent?->getTranslation('label', app()->getLocale()))
                ->toggleable(),
            TextColumn::make('sort_order')->sortable()->toggleable(),
            IconColumn::make('is_active')->label(__('admin.labels.active'))->boolean(),
            IconColumn::make('open_in_new_tab')->label(__('admin.labels.new_tab'))->boolean()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('menu_id')
                ->label(__('admin.labels.menu'))
                ->options(Menu::query()->get()->mapWithKeys(fn (Menu $menu) => [$menu->id => $menu->name]))->searchable(),
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
            ->emptyStateHeading(__('admin.empty_states.menu_items_resource.heading'))
            ->emptyStateDescription(__('admin.empty_states.menu_items_resource.description'));
    }
}
