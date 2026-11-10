<?php

namespace App\Filament\Admin\Resources\Menus\Tables;

use App\Filament\Admin\Resources\Menus\MenuResource;
use App\Models\Menu;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('name')->searchable(),
            TextColumn::make('location')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'header' => 'primary',
                    'footer' => 'info',
                    'user_dashboard' => 'warning',
                    'footer_browse' => 'success',
                    'footer_category' => 'success',
                    'footer_policies' => 'success',
                    'footer_social' => 'danger',
                    default => 'secondary',
                })
                ->sortable(),
            TextColumn::make('items_count')->label(__('admin.labels.items'))->counts('items')->sortable(),
            TextColumn::make('active_items_count')->label(__('admin.labels.active_items'))->sortable()->toggleable(),
            IconColumn::make('is_active')->label(__('admin.labels.active'))->boolean(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->since()->toggleable(),
        ])->filters([
            SelectFilter::make('location')->options([
                'header' => __('admin.options.header'),
                'footer' => __('admin.options.footer'),
                'user_dashboard' => __('admin.options.user_dashboard'),
                'footer_browse' => __('admin.options.footer_browse'),
                'footer_category' => __('admin.options.footer_category'),
                'footer_policies' => __('admin.options.footer_policies'),
                'footer_social' => __('admin.options.footer_social'),
            ])->searchable(),
            TernaryFilter::make('is_active')
                ->label(__('admin.labels.active'))
                ->trueLabel(__('admin.options.active'))
                ->falseLabel(__('admin.options.inactive')),
        ])->actions([
            Action::make('items')
                ->label(__('admin.labels.menu_items'))
                ->icon('heroicon-o-queue-list')
                ->url(fn (Menu $record): string => MenuResource::getUrl('edit', ['record' => $record])),
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('updated_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.menus.heading'))
            ->emptyStateDescription(__('admin.empty_states.menus.description'));
    }
}
