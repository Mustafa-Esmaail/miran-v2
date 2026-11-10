<?php

namespace App\Filament\Admin\Resources\Comments\Tables;

use App\Models\Comment;
use App\Services\CommentModerationService;
use App\Services\CommentService;
use App\Support\CommentAdminPresenter;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        $canModerate = auth('admin')->user()?->can('comments.update') ?? false;
        $canReply = auth('admin')->user()?->can('comments.update') ?? false;

        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('course_title')
                ->label(__('admin.labels.course'))
                ->state(fn(Comment $record): string => $record->adminContext()['course_title'])
                ->searchable(query: fn(Builder $query, string $search): Builder => Comment::applyAdminCourseSearch($query, $search))
                ->toggleable(),
            TextColumn::make('group_title')
                ->label(__('admin.labels.group'))
                ->state(fn(Comment $record): string => $record->adminContext()['group_title'])
                ->searchable(query: fn(Builder $query, string $search): Builder => Comment::applyAdminGroupSearch($query, $search))
                ->toggleable(),
            TextColumn::make('related_type')
                ->label(__('admin.labels.type'))
                ->state(fn(Comment $record): string => $record->adminContext()['related_type']),
            TextColumn::make('related_title')
                ->label(__('admin.labels.related_item'))
                ->state(fn(Comment $record): string => $record->adminContext()['related_title'])
                ->searchable(query: fn(Builder $query, string $search): Builder => Comment::applyAdminRelatedTitleSearch($query, $search))
                ->wrap()
                ->toggleable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('content')->label(__('admin.labels.comment'))->searchable()->limit(80),
            IconColumn::make('approved')->boolean()->label(__('admin.labels.approved')),
            IconColumn::make('hidden')->boolean()->label(__('admin.labels.hidden')),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            Filter::make('approved')
                ->label(__('admin.labels.approved'))
                ->query(fn(Builder $query) => $query->where('approved', true)),
            Filter::make('hidden')
                ->label(__('admin.labels.hidden'))
                ->query(fn(Builder $query) => $query->where('hidden', true)),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
            Action::make('reply')
                ->label(__('admin.actions.reply'))
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('info')
                ->visible(fn(Comment $record): bool => $canReply && $record->approved && ! $record->hidden)
                ->modalHeading(__('admin.modals.comments.reply_heading'))
                ->modalWidth('5xl')
                ->form(fn(Comment $record): array => [
                    Placeholder::make('comment_context')
                        ->label(__('admin.modals.comments.context_heading'))
                        ->content(fn() => app(CommentAdminPresenter::class)->contextHtml($record))
                        ->columnSpanFull(),
                    Placeholder::make('reply_history')
                        ->label(__('admin.modals.comments.history_heading'))
                        ->content(fn() => app(CommentAdminPresenter::class)->historyHtml($record))
                        ->columnSpanFull(),
                    Textarea::make('content')
                        ->label(__('admin.labels.reply'))
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                ])
                ->action(function (Comment $record, array $data, CommentService $service): void {
                    $user = auth('admin')->user();

                    if (! $user) {
                        return;
                    }

                    $service->addReply($record, $user, $data['content']);
                }),
            Action::make('approve')
                ->label(__('admin.labels.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn(Comment $record): bool => $canModerate && ! $record->approved)
                ->action(function (Comment $record, CommentModerationService $service) {
                    $service->approve($record);
                }),
            \Filament\Actions\Action::make('hide')
                ->label(__('admin.labels.hide'))
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->visible(fn(Comment $record): bool => $canModerate && ! $record->hidden)
                ->action(function (Comment $record, CommentModerationService $service) {
                    $service->hide($record);
                }),
            \Filament\Actions\Action::make('show')
                ->label(__('admin.labels.show'))
                ->icon('heroicon-o-eye')
                ->color('success')
                ->visible(fn(Comment $record): bool => $canModerate && $record->hidden)
                ->action(function (Comment $record, CommentModerationService $service) {
                    $service->show($record);
                }),
        ])->groupedBulkActions([
            BulkAction::make('approve')
                ->label(__('admin.labels.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($canModerate)
                ->action(function (Collection $records, CommentModerationService $service) {
                    $service->approveMany($records->pluck('id')->all());
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('hide')
                ->label(__('admin.labels.hide'))
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->visible($canModerate)
                ->action(function (Collection $records, CommentModerationService $service) {
                    $service->hideMany($records->pluck('id')->all());
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('show')
                ->label(__('admin.labels.show'))
                ->icon('heroicon-o-eye')
                ->color('success')
                ->visible($canModerate)
                ->action(function (Collection $records, CommentModerationService $service) {
                    $service->showMany($records->pluck('id')->all());
                })
                ->deselectRecordsAfterCompletion(),
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.comments.heading'))
            ->emptyStateDescription(__('admin.empty_states.comments.description'));
    }
}
