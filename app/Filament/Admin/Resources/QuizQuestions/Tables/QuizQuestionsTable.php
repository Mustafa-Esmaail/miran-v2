<?php

namespace App\Filament\Admin\Resources\QuizQuestions\Tables;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuizQuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('quiz.title')->label(__('admin.labels.quiz'))->searchable(),
            TextColumn::make('question')->limit(60)->searchable(),
            TextColumn::make('type')->sortable(),
            TextColumn::make('points')->sortable(),
            TextColumn::make('options_count')->label(__('admin.labels.options'))->counts('options')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('quiz_id')
                ->label(__('admin.labels.quiz'))
                ->relationship('quiz', 'title')
                ->searchable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.quiz_questions_resource.heading'))
            ->emptyStateDescription(__('admin.empty_states.quiz_questions_resource.description'));
    }
}
