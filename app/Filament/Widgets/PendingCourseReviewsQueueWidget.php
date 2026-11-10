<?php

namespace App\Filament\Widgets;

use App\Enums\CourseReviewStatus;
use App\Models\CourseReview;
use App\Services\CourseReviewService;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class PendingCourseReviewsQueueWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public function getHeading(): ?string
    {
        return __('admin.dashboard_widgets.queues.pending_course_reviews.heading');
    }

    public static function canView(): bool
    {
        return auth('admin')->user()?->can('course_reviews.viewAny') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CourseReview::query()
                    ->with(['course', 'user'])
                    ->where('status', CourseReviewStatus::Pending)
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('course.title')
                    ->label(__('admin.labels.course'))
                    ->state(fn (CourseReview $record): string => $record->course?->getTranslation('title', app()->getLocale()) ?? '-')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label(__('admin.labels.user'))
                    ->searchable(),
                TextColumn::make('rating')
                    ->label(__('admin.labels.rating')),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.submitted'))
                    ->since(),
            ])
            ->actions([
                Action::make('approve')
                    ->label(__('admin.actions.approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (): bool => auth('admin')->user()?->can('course_reviews.update') ?? false)
                    ->action(function (CourseReview $record, CourseReviewService $service): void {
                        $service->approve($record);
                    }),
                Action::make('reject')
                    ->label(__('admin.actions.reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (): bool => auth('admin')->user()?->can('course_reviews.update') ?? false)
                    ->action(function (CourseReview $record, CourseReviewService $service): void {
                        $service->reject($record);
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.dashboard_widgets.queues.pending_course_reviews.empty'));
    }
}
