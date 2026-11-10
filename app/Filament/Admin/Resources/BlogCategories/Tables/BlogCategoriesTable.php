<?php

namespace App\Filament\Admin\Resources\BlogCategories\Tables;

use App\Enums\BlogCategoryStatus;
use App\Models\BlogCategory;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BlogCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn (BlogCategory $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('slug')->searchable(),
            TextColumn::make('blogs_count')->label(__('admin.labels.posts'))->counts('blogs')->sortable()->badge(),
            IconColumn::make('status')
                ->label(__('admin.labels.active'))
                ->boolean(fn (BlogCategory $record) => $record->status === BlogCategoryStatus::Active),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('status')->options([
                BlogCategoryStatus::Active->value => __('admin.options.active'),
                BlogCategoryStatus::Inactive->value => __('admin.options.inactive'),
            ])->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.blog_categories.heading'))
            ->emptyStateDescription(__('admin.empty_states.blog_categories.description'));
    }
}
