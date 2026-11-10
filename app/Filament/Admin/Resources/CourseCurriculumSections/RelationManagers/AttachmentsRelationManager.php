<?php

namespace App\Filament\Admin\Resources\CourseCurriculumSections\RelationManagers;

use App\Enums\AttachmentVisibility;
use App\Filament\Admin\Resources\CourseAttachments\CourseAttachmentResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_attachments');
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
                TextColumn::make('visibility')
                    ->badge()
                    ->color(function ($state): string {
                        $value = $state instanceof AttachmentVisibility ? $state->value : (string) $state;

                        return match ($value) {
                            AttachmentVisibility::Public->value => 'success',
                            AttachmentVisibility::EnrolledOnly->value => 'warning',
                            default => 'secondary',
                        };
                    }),
                TextColumn::make('file_path')->label(__('admin.labels.file'))->limit(30)->toggleable(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('admin.actions.create'))
                    ->url(fn () => CourseAttachmentResource::getUrl('create')),
            ])
            ->actions([
                Action::make('preview')
                    ->label(__('admin.actions.preview'))
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record): ?string => $record->file_path
                        ? rescue(function () use ($record): ?string {
                            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                            $disk = Storage::disk('s3');

                            return $disk->temporaryUrl($record->file_path, now()->addMinutes(30));
                        }, null, false)
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn ($record): bool => (bool) $record->file_path),
                Action::make('view')
                    ->label(__('admin.actions.view'))
                    ->url(fn ($record) => CourseAttachmentResource::getUrl('edit', ['record' => $record])),
                EditAction::make()
                    ->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => CourseAttachmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => CourseAttachmentResource::getUrl('edit', ['record' => $record]));
    }
}
