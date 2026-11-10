<?php

namespace App\Filament\Admin\Resources\QuizAttempts\Tables;

use App\Models\QuizAttempt;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuizAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('quiz.title')->label(__('admin.labels.quiz'))->searchable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('user.email')->label(__('admin.labels.email'))->searchable()->toggleable(),
            TextColumn::make('attempt_number')
                ->label(__('admin.labels.attempt_number'))
                ->badge()
                ->color('gray')
                ->sortable(false),
            TextColumn::make('score')->label(__('admin.labels.score'))->sortable(),
            TextColumn::make('pass_status')
                ->label(__('admin.labels.pass_status'))
                ->badge()
                ->state(fn (QuizAttempt $record): string => $record->isPassed() ? 'passed' : 'failed')
                ->formatStateUsing(fn (string $state): string => $state === 'passed'
                    ? __('admin.labels.passed')
                    : __('admin.labels.failed'))
                ->color(fn (string $state): string => $state === 'passed' ? 'success' : 'danger'),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('quiz_id')
                ->label(__('admin.labels.quiz'))
                ->relationship('quiz', 'title')
                ->searchable(),
            SelectFilter::make('user_id')
                ->label(__('admin.labels.user'))
                ->relationship('user', 'email')
                ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->email ?? $record->name ?? $record->phone ?? (string) $record->id)->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
            Action::make('answers')
                ->label(__('admin.labels.view_answers'))
                ->modalHeading(__('admin.modals.quiz_attempts.answers_heading'))
                ->modalWidth(Width::FiveExtraLarge)
                ->modalContent(fn (QuizAttempt $record) => view('filament.quiz-attempt-answers', [
                    'record' => $record,
                    'allAttempts' => QuizAttempt::query()
                        ->with(['quiz.questions.options'])
                        ->where('quiz_id', $record->quiz_id)
                        ->where('user_id', $record->user_id)
                        ->orderBy('id')
                        ->get(),
                ]))
                ->modalSubmitAction(false),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.quiz_attempts_resource.heading'))
            ->emptyStateDescription(__('admin.empty_states.quiz_attempts_resource.description'));
    }
}
