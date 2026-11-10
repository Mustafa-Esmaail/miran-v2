<?php

namespace App\Filament\Widgets;

use App\Enums\BlogCommentStatus;
use App\Models\BlogComment;
use App\Services\BlogCommentService;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingBlogCommentsQueueWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.queues.pending_blog_comments.heading');
    }

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('blog_comments.viewAny') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BlogComment::query()
                    ->with(['blog', 'user'])
                    ->where('status', BlogCommentStatus::Pending)
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('blog.title')
                    ->label(__('admin.labels.post'))
                    ->state(fn (BlogComment $record): string => $record->blog?->getTranslation('title', app()->getLocale()) ?? '-')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label(__('admin.labels.user'))
                    ->searchable(),
                TextColumn::make('content')
                    ->label(__('admin.labels.comment'))
                    ->state(fn (BlogComment $record): string => $record->getTranslation('content', app()->getLocale()))
                    ->limit(40),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.submitted'))
                    ->since(),
            ])
            ->actions([
                Action::make('approve')
                    ->label(__('admin.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (): bool => auth('admin')->user()?->can('blog_comments.update') ?? false)
                    ->action(function (BlogComment $record, BlogCommentService $service): void {
                        $service->approve($record);
                    }),
                Action::make('reject')
                    ->label(__('admin.actions.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (): bool => auth('admin')->user()?->can('blog_comments.update') ?? false)
                    ->action(function (BlogComment $record, BlogCommentService $service): void {
                        $service->reject($record);
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard_widgets.queues.pending_blog_comments.empty'));
    }
}
