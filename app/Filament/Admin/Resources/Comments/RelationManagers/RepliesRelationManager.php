<?php

namespace App\Filament\Admin\Resources\Comments\RelationManagers;

use App\Models\Comment;
use App\Models\CommentReply;
use App\Services\CommentModerationService;
use App\Services\CommentService;
use App\Support\CommentAdminPresenter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepliesRelationManager extends RelationManager
{
    protected static string $relationship = 'allReplies';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.comment_replies');
    }

    public function table(Table $table): Table
    {
        $canModerate = auth('admin')->user()?->can('comments.update') ?? false;

        return $table
            ->columns([
                TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
                TextColumn::make('parentReply.user.name')->label(__('admin.labels.parent_reply'))->placeholder('-'),
                TextColumn::make('content')->label(__('admin.labels.reply'))->wrap()->limit(100),
                IconColumn::make('approved')->boolean()->label(__('admin.labels.approved')),
                IconColumn::make('hidden')->boolean()->label(__('admin.labels.hidden')),
                TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('admin.actions.reply'))
                    ->visible($canModerate)
                    ->modalHeading(__('admin.modals.comments.reply_heading'))
                    ->modalWidth('5xl')
                    ->form(fn (): array => [
                        Placeholder::make('comment_context')
                            ->label(__('admin.modals.comments.context_heading'))
                            ->content(fn () => app(CommentAdminPresenter::class)->contextHtml($this->getOwnerRecord()))
                            ->columnSpanFull(),
                        Placeholder::make('reply_history')
                            ->label(__('admin.modals.comments.history_heading'))
                            ->content(fn () => app(CommentAdminPresenter::class)->historyHtml($this->getOwnerRecord()))
                            ->columnSpanFull(),
                        Textarea::make('content')
                            ->label(__('admin.labels.reply'))
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->using(function (array $data): CommentReply {
                        /** @var Comment $comment */
                        $comment = $this->getOwnerRecord();

                        return app(CommentService::class)->addReply($comment, auth('admin')->user(), $data['content']);
                    }),
            ])
            ->actions([
                Action::make('reply')
                    ->label(__('admin.actions.reply'))
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->visible($canModerate)
                    ->modalHeading(__('admin.modals.comments.reply_heading'))
                    ->modalWidth('5xl')
                    ->form(fn (CommentReply $record): array => [
                        Placeholder::make('comment_context')
                            ->label(__('admin.modals.comments.context_heading'))
                            ->content(fn () => app(CommentAdminPresenter::class)->contextHtml($this->getOwnerRecord()))
                            ->columnSpanFull(),
                        Placeholder::make('reply_history')
                            ->label(__('admin.modals.comments.history_heading'))
                            ->content(fn () => app(CommentAdminPresenter::class)->historyHtml($this->getOwnerRecord(), $record))
                            ->columnSpanFull(),
                        Textarea::make('content')
                            ->label(__('admin.labels.reply'))
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
                    ->action(function (CommentReply $record, array $data): void {
                        /** @var Comment $comment */
                        $comment = $this->getOwnerRecord();

                        app(CommentService::class)->addReply($comment, auth('admin')->user(), $data['content'], $record);
                    }),
                Action::make('approve')
                    ->label(__('admin.labels.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (CommentReply $record): bool => $canModerate && ! $record->approved)
                    ->action(fn (CommentReply $record, CommentModerationService $service) => $service->approveReply($record)),
                Action::make('hide')
                    ->label(__('admin.labels.hide'))
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->visible(fn (CommentReply $record): bool => $canModerate && ! $record->hidden)
                    ->action(fn (CommentReply $record, CommentModerationService $service) => $service->hideReply($record)),
                Action::make('show')
                    ->label(__('admin.labels.show'))
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->visible(fn (CommentReply $record): bool => $canModerate && $record->hidden)
                    ->action(fn (CommentReply $record, CommentModerationService $service) => $service->showReply($record)),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
