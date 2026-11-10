<?php

namespace App\Filament\Admin\Resources\CourseQuestions\Tables;

use App\Models\CourseQuestion;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourseQuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('course.title')
                ->label(__('admin.labels.course'))
                ->state(fn (CourseQuestion $record) => $record->course?->getTranslation('title', app()->getLocale()) ?? '-')
                ->searchable(),
            TextColumn::make('title')
                ->label(__('admin.labels.question'))
                ->limit(60)
                ->wrap()
                ->searchable(),
            TextColumn::make('user.name')->label(__('admin.labels.user'))->searchable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.course_questions.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_questions.description'));
    }
}
