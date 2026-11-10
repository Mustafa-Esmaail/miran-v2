<?php

namespace App\Filament\Admin\Resources\CourseReviews\Tables;

use App\Enums\CourseReviewStatus;
use App\Models\CourseReview;
use App\Services\CourseReviewService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CourseReviewsTable
{
    public static function configure(Table $table): Table
    {
        $canModerate = auth('admin')->user()?->can('course_reviews.update') ?? false;

        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('course.title')
                ->label(__('admin.labels.course'))
                ->state(fn (CourseReview $record) => $record->course?->getTranslation('title', app()->getLocale()) ?? '-')
                ->searchable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('rating')->label(__('admin.labels.rating'))->sortable(),
            TextColumn::make('content')->label(__('admin.labels.review'))->limit(60)->wrap(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof CourseReviewStatus ? $state->value : (string) $state;

                    return match ($value) {
                        CourseReviewStatus::Approved->value => 'success',
                        CourseReviewStatus::Pending->value => 'warning',
                        CourseReviewStatus::Rejected->value => 'danger',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('status')->options([
                CourseReviewStatus::Pending->value => __('admin.options.pending'),
                CourseReviewStatus::Approved->value => __('admin.options.approved'),
                CourseReviewStatus::Rejected->value => __('admin.options.rejected'),
            ])->searchable(),
        ])->actions([
            ViewAction::make()->label(__('admin.pages.view')),
            Action::make('approve')
                ->label(__('admin.labels.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (CourseReview $record): bool => $canModerate && $record->status === CourseReviewStatus::Pending)
                ->action(function (CourseReview $record, CourseReviewService $service) {
                    $service->approve($record);
                }),
            Action::make('reject')
                ->label(__('admin.labels.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (CourseReview $record): bool => $canModerate && $record->status !== CourseReviewStatus::Rejected)
                ->action(function (CourseReview $record, CourseReviewService $service) {
                    $service->reject($record);
                }),
        ])->groupedBulkActions([
            BulkAction::make('approve')
                ->label(__('admin.labels.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($canModerate)
                ->action(function (Collection $records, CourseReviewService $service) {
                    $service->approveMany($records->pluck('id')->all());
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('reject')
                ->label(__('admin.labels.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible($canModerate)
                ->action(function (Collection $records, CourseReviewService $service) {
                    $service->rejectMany($records->pluck('id')->all());
                })
                ->deselectRecordsAfterCompletion(),
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.course_reviews.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_reviews.description'));
    }
}
