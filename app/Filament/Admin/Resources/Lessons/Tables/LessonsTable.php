<?php

namespace App\Filament\Admin\Resources\Lessons\Tables;

use App\Enums\LessonType;
use App\Models\Course;
use App\Models\Lesson;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('course.slug')->label(__('admin.labels.course'))->searchable(),
            TextColumn::make('course_title')
                ->label(__('admin.labels.course_title'))
                ->state(fn (Lesson $record) => $record->course?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('curriculum_section_title')
                ->label(__('admin.labels.curriculum_section'))
                ->state(fn (Lesson $record) => $record->curriculumSection?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn (Lesson $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('type')
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof LessonType ? $state->value : (string) $state;

                    return match ($value) {
                        LessonType::Video->value => 'primary',
                        LessonType::Pdf->value => 'gray',
                        LessonType::Text->value => 'info',
                        default => 'secondary',
                    };
                })
                ->sortable(),
            TextColumn::make('sort_order')->sortable(),
            IconColumn::make('is_preview')->label(__('admin.labels.preview'))->boolean(),
            TextColumn::make('video_url')->label(__('admin.labels.video_url'))->limit(30)->toggleable(),
            TextColumn::make('pdf_path')->label(__('admin.labels.pdf'))->limit(30)->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
            SelectFilter::make('course_id')
                ->relationship('course', 'slug')
                ->getOptionLabelFromRecordUsing(fn (Course $record): string => $record->getTranslation('title', app()->getLocale()))
                ->label(__('admin.labels.course'))
                ->searchable(),
            SelectFilter::make('type')->options([
                LessonType::Pdf->value => __('admin.options.pdf'),
                LessonType::Text->value => __('admin.options.text'),
                LessonType::Video->value => __('admin.options.video'),
            ])->searchable(),
            TernaryFilter::make('is_preview')
                ->label(__('admin.labels.preview'))
                ->trueLabel(__('admin.options.yes'))
                ->falseLabel(__('admin.options.no')),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.lessons.heading'))
            ->emptyStateDescription(__('admin.empty_states.lessons.description'));
    }
}
