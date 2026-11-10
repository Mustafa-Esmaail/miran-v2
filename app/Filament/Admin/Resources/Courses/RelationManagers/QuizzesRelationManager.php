<?php

namespace App\Filament\Admin\Resources\Courses\RelationManagers;

use App\Filament\Admin\Resources\Quizzes\QuizResource;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuizzesRelationManager extends RelationManager
{
    protected static string $relationship = 'quizzes';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_quizzes');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.quiz_details'))
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->placeholder(__('admin.placeholders.final_assessment')),
                    TextInput::make('passing_score')
                        ->numeric()
                        ->suffix('%')
                        ->helperText(__('admin.helpers.score_required_to_pass')),
                ])
                ->columns(2),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')->label(__('admin.labels.title'))->searchable(),
            TextColumn::make('passing_score')
                ->formatStateUsing(fn ($state) => $state !== null ? $state.'%' : '-')
                ->sortable(),
            TextColumn::make('questions_count')->label(__('admin.labels.questions'))->counts('questions')->sortable(),
            TextColumn::make('attempts_count')->label(__('admin.labels.attempts'))->counts('attempts')->sortable(),
        ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])
            ->actions([
                EditAction::make()->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => QuizResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => QuizResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('admin.empty_states.quizzes.heading'))
            ->emptyStateDescription(__('admin.empty_states.quizzes.description'));
    }
}
