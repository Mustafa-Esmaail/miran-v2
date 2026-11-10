<?php

namespace App\Filament\Admin\Resources\Quizzes\RelationManagers;

use App\Filament\Admin\Resources\QuizAttempts\QuizAttemptResource;
use App\Models\QuizAttempt;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AttemptsRelationManager extends RelationManager
{
    protected static string $relationship = 'attempts';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.quiz_attempts');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth('admin')->user()?->can('quizzes.viewAny') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('user.email')->label(__('admin.labels.email'))->searchable()->toggleable(),

            TextColumn::make('attempt_number')
                ->label(__('admin.labels.attempt_number'))
                ->badge()
                ->color('gray')
                ->state(fn (QuizAttempt $record): int => $record->getAttemptNumber()),

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
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create'))
                ->modal(false)
                ->url(fn () => QuizAttemptResource::getUrl('create', [
                    'quiz_id' => $this->getOwnerRecord()->id,
                ])),
        ])->actions([
            EditAction::make()->label(__('admin.actions.edit'))
                ->modal(false)
                ->url(fn ($record) => QuizAttemptResource::getUrl('edit', ['record' => $record])),
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
            DeleteAction::make()->label(__('admin.actions.delete')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.quiz_attempts.heading'))
            ->emptyStateDescription(__('admin.empty_states.quiz_attempts.description'));
    }
}
