<?php

namespace App\Filament\Admin\Resources\Courses\RelationManagers;

use App\Enums\AttachmentVisibility;
use App\Filament\Admin\Resources\CourseAttachments\CourseAttachmentResource;
use App\Filament\Forms\TranslatableField;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
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

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.attachment'))
                ->schema([
                    TranslatableField::localeSwitcher(),
                    Select::make('course_group_id')
                        ->label(__('admin.labels.group'))
                        ->options(fn () => CourseGroup::query()
                            ->where('course_id', $this->getOwnerRecord()->id)
                            ->get()
                            ->mapWithKeys(fn (CourseGroup $group) => [
                                $group->id => $group->getTranslation('title', app()->getLocale()),
                            ])
                            ->all())
                        ->required(fn (Get $get): bool => AttachmentVisibility::sectionRequiredFor($get('visibility')))
                        ->visible(fn (Get $get): bool => $get('visibility') !== AttachmentVisibility::General->value)
                        ->searchable()
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(fn (Set $set) => $set('course_curriculum_section_id', null)),
                    Select::make('course_curriculum_section_id')
                        ->label(__('admin.labels.curriculum_section'))
                        ->options(fn (Get $get) => CourseCurriculumSection::query()
                            ->where('course_group_id', $get('course_group_id'))
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (CourseCurriculumSection $section) => [
                                $section->id => $section->getTranslation('title', app()->getLocale()),
                            ])
                            ->all())
                        ->required(fn (Get $get): bool => AttachmentVisibility::sectionRequiredFor($get('visibility')))
                        ->visible(fn (Get $get): bool => $get('visibility') !== AttachmentVisibility::General->value)
                        ->searchable(),
                    TranslatableField::text('title', __('admin.fields.title'), true),
                    FileUpload::make('file_path')
                        ->disk('s3')
                        ->directory('course-attachments')
                        ->visibility('private')
                        ->openable()
                        ->helperText(__('admin.helpers.upload_slides_worksheets_or_resources')),
                    Select::make('visibility')->options([
                        AttachmentVisibility::Public->value => __('admin.options.public'),
                        AttachmentVisibility::EnrolledOnly->value => __('admin.options.enrolled_only'),
                        AttachmentVisibility::Profile->value => __('admin.options.profile'),
                        AttachmentVisibility::General->value => __('admin.options.general'),
                    ])->live()->required(),
                ])
                ->columns(2),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->formatStateUsing(fn ($state, $record) => $record->getTranslation('title', app()->getLocale()))
                ->searchable(),
            TextColumn::make('visibility')
                ->badge()
                ->color(function ($state): string {
                    $value = $state instanceof AttachmentVisibility ? $state->value : (string) $state;

                    return match ($value) {
                        AttachmentVisibility::Public->value => 'success',
                        AttachmentVisibility::EnrolledOnly->value => 'warning',
                        AttachmentVisibility::Profile->value => 'info',
                        AttachmentVisibility::General->value => 'primary',
                        default => 'secondary',
                    };
                }),
            TextColumn::make('curriculum_section_title')
                ->label(__('admin.labels.curriculum_section'))
                ->state(fn ($record) => $record->curriculumSection?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('file_path')->label(__('admin.labels.file'))->limit(30)->toggleable(),
        ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
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
                EditAction::make()->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => CourseAttachmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => CourseAttachmentResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('admin.empty_states.course_attachments_relation.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_attachments_relation.description'));
    }
}
