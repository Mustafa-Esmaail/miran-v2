<?php

namespace App\Filament\Widgets;

use App\Filament\Admin\Resources\Comments\CommentResource;
use App\Models\Comment;
use App\Services\CommentService;
use App\Support\CommentAdminPresenter;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestCommentsWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 12,
    ];

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.queues.latest_comments.heading');
    }

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('comments.viewAny') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Comment::applyAdminContextEagerLoading(Comment::query())
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('admin.labels.user'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('course_title')
                    ->label(__('admin.labels.course'))
                    ->state(fn (Comment $record): string => $record->adminContext()['course_title'])
                    ->searchable(query: fn (Builder $query, string $search): Builder => Comment::applyAdminCourseSearch($query, $search))
                    ->toggleable(),
                TextColumn::make('group_title')
                    ->label(__('admin.labels.group'))
                    ->state(fn (Comment $record): string => $record->adminContext()['group_title'])
                    ->searchable(query: fn (Builder $query, string $search): Builder => Comment::applyAdminGroupSearch($query, $search))
                    ->toggleable(),
                TextColumn::make('related_type')
                    ->label(__('admin.labels.type'))
                    ->state(fn (Comment $record): string => $record->adminContext()['related_type']),
                TextColumn::make('related_title')
                    ->label(__('admin.labels.related_item'))
                    ->state(fn (Comment $record): string => $record->adminContext()['related_title'])
                    ->searchable(query: fn (Builder $query, string $search): Builder => Comment::applyAdminRelatedTitleSearch($query, $search))
                    ->limit(40)
                    ->wrap(),
                TextColumn::make('content')
                    ->label(__('admin.labels.comment'))
                    ->searchable()
                    ->wrap(),
                // ->limit(90),
                IconColumn::make('approved')
                    ->label(__('admin.labels.approved'))
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.created_at'))
                    ->since(),
            ])
            ->actions([
                Action::make('reply')
                    ->label(__('admin.actions.reply'))
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('info')
                    ->visible(fn (Comment $record): bool => (auth('admin')->user()?->can('comments.update') ?? false) && $record->approved && ! $record->hidden)
                    ->modalHeading(__('admin.modals.comments.reply_heading'))
                    ->modalWidth('5xl')
                    ->form(fn (Comment $record): array => [
                        Placeholder::make('comment_context')
                            ->label(__('admin.modals.comments.context_heading'))
                            ->content(fn () => app(CommentAdminPresenter::class)->contextHtml($record))
                            ->columnSpanFull(),
                        Placeholder::make('reply_history')
                            ->label(__('admin.modals.comments.history_heading'))
                            ->content(fn () => app(CommentAdminPresenter::class)->historyHtml($record))
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
                Action::make('edit')
                    ->label(__('admin.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (Comment $record): string => CommentResource::getUrl('edit', ['record' => $record])),
            ])
            ->headerActions([
                Action::make('view_all')
                    ->label(__('admin.actions.view_all'))
                    ->icon('heroicon-o-list-bullet')
                    ->url(CommentResource::getUrl('index')),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard_widgets.queues.latest_comments.empty'));
    }
}
