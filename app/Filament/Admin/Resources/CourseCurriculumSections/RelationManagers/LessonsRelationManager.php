<?php

namespace App\Filament\Admin\Resources\CourseCurriculumSections\RelationManagers;

use App\Enums\LessonType;
use App\Filament\Admin\Resources\Lessons\LessonResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_lessons');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('title')
                    ->label(__('admin.labels.title'))
                    ->state(fn ($record) => $record->getTranslation('title', app()->getLocale()))
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
                    }),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('admin.actions.create'))
                    ->url(fn () => LessonResource::getUrl('create')),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('admin.actions.view'))
                    ->url(fn ($record) => LessonResource::getUrl('edit', ['record' => $record])),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => LessonResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => LessonResource::getUrl('edit', ['record' => $record]));
    }
}
