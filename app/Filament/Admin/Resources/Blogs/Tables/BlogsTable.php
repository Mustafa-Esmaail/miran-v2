<?php

namespace App\Filament\Admin\Resources\Blogs\Tables;

use App\Enums\BlogStatus;
use App\Models\Blog;
use App\Services\BlogService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BlogsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn (Blog $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('category.title')
                ->label(__('admin.labels.category'))
                ->state(fn (Blog $record) => $record->category?->getTranslation('title', app()->getLocale()) ?? '-')
                ->sortable(),
            TextColumn::make('author.name')->label(__('admin.labels.author'))->sortable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof BlogStatus ? $state->value : (string) $state;

                    return match ($value) {
                        BlogStatus::Published->value => 'success',
                        BlogStatus::Draft->value => 'gray',
                        BlogStatus::Archived->value => 'danger',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('published_at')->label(__('admin.labels.published_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('category_id')
                ->relationship('category', 'title')
                ->label(__('admin.labels.category'))
                ->searchable(),
            SelectFilter::make('status')
                ->options([
                    BlogStatus::Draft->value => __('admin.options.draft'),
                    BlogStatus::Published->value => __('admin.options.published'),
                    BlogStatus::Archived->value => __('admin.options.archived'),
                ])->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            Action::make('preview_on_site')
                ->label(__('admin.labels.preview'))
                ->icon('heroicon-o-eye')
                ->url(fn (Blog $record): string => route('blog.show', $record->slug))
                ->openUrlInNewTab(),
            Action::make('publish')
                ->label(__('admin.labels.publish'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (Blog $record): bool => $record->status !== BlogStatus::Published)
                ->action(function (Blog $record, BlogService $service) {
                    $service->publish($record);
                })
                ->requiresConfirmation(),
            Action::make('unpublish')
                ->label(__('admin.labels.unpublish'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Blog $record): bool => $record->status === BlogStatus::Published)
                ->action(function (Blog $record, BlogService $service) {
                    $service->unpublish($record);
                })
                ->requiresConfirmation(),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.blog_posts.heading'))
            ->emptyStateDescription(__('admin.empty_states.blog_posts.description'));
    }
}
