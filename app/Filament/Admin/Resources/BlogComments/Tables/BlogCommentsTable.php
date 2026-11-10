<?php

namespace App\Filament\Admin\Resources\BlogComments\Tables;

use App\Enums\BlogCommentStatus;
use App\Models\BlogComment;
use App\Services\BlogCommentService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class BlogCommentsTable
{
    public static function configure(Table $table): Table
    {
        $canModerate = auth('admin')->user()?->can('blog_comments.update') ?? false;

        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('blog.title')
                ->label(__('admin.labels.post'))
                ->state(fn (BlogComment $record) => $record->blog?->getTranslation('title', app()->getLocale()) ?? '-')
                ->searchable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('content')
                ->label(__('admin.labels.comment'))
                ->state(fn (BlogComment $record) => $record->getTranslation('content', app()->getLocale()))
                ->limit(60)
                ->wrap(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof BlogCommentStatus ? $state->value : (string) $state;

                    return match ($value) {
                        BlogCommentStatus::Approved->value => 'success',
                        BlogCommentStatus::Pending->value => 'warning',
                        BlogCommentStatus::Rejected->value => 'danger',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('status')->options([
                BlogCommentStatus::Pending->value => __('admin.options.pending'),
                BlogCommentStatus::Approved->value => __('admin.options.approved'),
                BlogCommentStatus::Rejected->value => __('admin.options.rejected'),
            ])->searchable(),
        ])->actions([
            ViewAction::make()->label(__('admin.pages.view')),
            Action::make('approve')
                ->label(__('admin.labels.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (BlogComment $record): bool => $canModerate && $record->status === BlogCommentStatus::Pending)
                ->action(function (BlogComment $record, BlogCommentService $service) {
                    $service->approve($record);
                }),
            Action::make('reject')
                ->label(__('admin.labels.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (BlogComment $record): bool => $canModerate && $record->status !== BlogCommentStatus::Rejected)
                ->action(function (BlogComment $record, BlogCommentService $service) {
                    $service->reject($record);
                }),
        ])->groupedBulkActions([
            BulkAction::make('approve')
                ->label(__('admin.labels.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($canModerate)
                ->action(function (Collection $records, BlogCommentService $service) {
                    $service->approveMany($records->pluck('id')->all());
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('reject')
                ->label(__('admin.labels.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible($canModerate)
                ->action(function (Collection $records, BlogCommentService $service) {
                    $service->rejectMany($records->pluck('id')->all());
                })
                ->deselectRecordsAfterCompletion(),
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.blog_comments_resource.heading'))
            ->emptyStateDescription(__('admin.empty_states.blog_comments_resource.description'));
    }
}
