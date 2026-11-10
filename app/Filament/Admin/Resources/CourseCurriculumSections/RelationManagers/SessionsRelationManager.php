<?php

namespace App\Filament\Admin\Resources\CourseCurriculumSections\RelationManagers;

use App\Filament\Admin\Resources\CourseGroupSessions\CourseGroupSessionResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_sessions');
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('courseGroup.title')
                    ->label(__('admin.labels.group_title'))
                    ->state(fn ($record) => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-')
                    ->toggleable(),
                TextColumn::make('start_at')->dateTime('Y-m-d H:i'),
                TextColumn::make('duration_minutes')
                    ->label(__('admin.labels.duration'))
                    ->formatStateUsing(fn ($state) => $state ? $state.' min' : '-'),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('admin.actions.create'))
                    ->url(fn () => CourseGroupSessionResource::getUrl('create')),
            ])
            ->actions([
                Action::make('view')
                    ->label(__('admin.actions.view'))
                    ->url(fn ($record) => CourseGroupSessionResource::getUrl('edit', ['record' => $record])),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => CourseGroupSessionResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => CourseGroupSessionResource::getUrl('edit', ['record' => $record]));
    }
}
