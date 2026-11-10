<?php

namespace App\Filament\Admin\Resources\QuestionAnswers\Tables;

use App\Enums\QuestionAnswerStatus;
use App\Models\QuestionAnswer;
use App\Services\AnswerService;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class QuestionAnswersTable
{
    public static function configure(Table $table): Table
    {
        $canModerate = auth('admin')->user()?->can('question_answers.update') ?? false;

        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('question.title')->label(__('admin.labels.question'))->limit(50)->wrap()->searchable(),
            TextColumn::make('question.course.title')
                ->label(__('admin.labels.course'))
                ->state(fn (QuestionAnswer $record) => $record->question?->course?->getTranslation('title', app()->getLocale()) ?? '-')
                ->sortable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof QuestionAnswerStatus ? $state->value : (string) $state;

                    return match ($value) {
                        QuestionAnswerStatus::Approved->value => 'success',
                        QuestionAnswerStatus::Pending->value => 'warning',
                        QuestionAnswerStatus::Rejected->value => 'danger',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            IconColumn::make('is_accepted')->boolean()->label(__('admin.labels.accepted')),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('status')->options([
                QuestionAnswerStatus::Pending->value => __('admin.options.pending'),
                QuestionAnswerStatus::Approved->value => __('admin.options.approved'),
                QuestionAnswerStatus::Rejected->value => __('admin.options.rejected'),
            ])->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
            \Filament\Actions\Action::make('approve')
                ->label(__('admin.labels.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (QuestionAnswer $record): bool => $canModerate && $record->status === QuestionAnswerStatus::Pending)
                ->action(function (QuestionAnswer $record, AnswerService $service) {
                    $service->approve($record);
                }),
            \Filament\Actions\Action::make('reject')
                ->label(__('admin.labels.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (QuestionAnswer $record): bool => $canModerate && $record->status !== QuestionAnswerStatus::Rejected)
                ->action(function (QuestionAnswer $record, AnswerService $service) {
                    $service->reject($record);
                }),
        ])->groupedBulkActions([
            BulkAction::make('approve')
                ->label(__('admin.labels.approve'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible($canModerate)
                ->action(function (Collection $records, AnswerService $service) {
                    $service->approveMany($records->pluck('id')->all());
                })
                ->deselectRecordsAfterCompletion(),
            BulkAction::make('reject')
                ->label(__('admin.labels.reject'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible($canModerate)
                ->action(function (Collection $records, AnswerService $service) {
                    $service->rejectMany($records->pluck('id')->all());
                })
                ->deselectRecordsAfterCompletion(),
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.question_answers.heading'))
            ->emptyStateDescription(__('admin.empty_states.question_answers.description'));
    }
}
