<?php

namespace App\Filament\Admin\Resources\Courses\RelationManagers;

use App\Filament\Admin\Resources\Assignments\AssignmentResource;
use App\Filament\Forms\TranslatableField;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.relations.course_assignments');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.assignment_details'))
                ->schema([
                    Select::make('course_group_id')
                        ->label(__('admin.labels.group'))
                        ->helperText(__('admin.helpers.assignment_no_group'))
                        ->options(fn () => CourseGroup::query()
                            ->where('course_id', $this->getOwnerRecord()->id)
                            ->get()
                            ->mapWithKeys(fn (CourseGroup $group) => [
                                $group->id => $group->getTranslation('title', app()->getLocale()) ?? ('Group #'.$group->id),
                            ])
                            ->all())
                        ->preload()
                        ->searchable(),
                    Select::make('course_curriculum_section_id')
                        ->label(__('admin.labels.curriculum_section'))
                        ->options(fn (Get $get) => CourseCurriculumSection::query()
                            ->where('course_id', $this->getOwnerRecord()->id)
                            ->where('course_group_id', $get('course_group_id'))
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (CourseCurriculumSection $section) => [
                                $section->id => $section->getTranslation('title', app()->getLocale()),
                            ])
                            ->all())
                        ->required()
                        ->preload()
                        ->searchable(),
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true),
                    DateTimePicker::make('due_at')
                        ->label(__('admin.labels.due_at'))
                        ->seconds(false),
                    TextInput::make('max_grade')
                        ->label(__('admin.labels.max_grade'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->default(10)
                        ->required(),
                    Toggle::make('is_published')
                        ->label(__('admin.labels.published'))
                        ->default(true),
                    RichEditor::make('description')
                        ->label(__('admin.labels.content'))
                        ->fileAttachmentsDisk('s3')
                        ->fileAttachmentsDirectory('assignments/description')
                        ->fileAttachmentsVisibility('private')
                        ->columnSpanFull(),
                    FileUpload::make('attachment_path')
                        ->label(__('admin.labels.attachments'))
                        ->disk('s3')
                        ->directory('assignments/attachments')
                        ->visibility('private')
                        ->multiple()
                        ->reorderable()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ])->columns(1);
    }

    public function table(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn ($record) => $record->getTranslation('title', $locale))
                ->searchable(),
            TextColumn::make('group_title')
                ->label(__('admin.labels.group'))
                ->state(fn ($record) => $record->courseGroup?->getTranslation('title', $locale) ?? __('admin.labels.all_groups'))
                ->toggleable(),
            TextColumn::make('curriculum_section_title')
                ->label(__('admin.labels.curriculum_section'))
                ->state(fn ($record) => $record->curriculumSection?->getTranslation('title', $locale) ?? '-')
                ->toggleable(),
            TextColumn::make('due_at')
                ->label(__('admin.labels.due_at'))
                ->dateTime('Y-m-d H:i')
                ->placeholder('-')
                ->sortable(),
            TextColumn::make('max_grade')->label(__('admin.labels.max_grade'))->sortable(),
            TextColumn::make('submissions_count')
                ->label(__('admin.labels.submissions_count'))
                ->counts('submissions')
                ->toggleable(),
            IconColumn::make('is_published')->label(__('admin.labels.published'))->boolean(),
        ])
            ->headerActions([
                CreateAction::make()->label(__('admin.actions.create')),
            ])
            ->actions([
                EditAction::make()->label(__('admin.actions.edit'))
                    ->modal(false)
                    ->url(fn ($record) => AssignmentResource::getUrl('edit', ['record' => $record])),
            ])
            ->recordUrl(fn ($record) => AssignmentResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('admin.empty_states.course_assignments.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_assignments.description'));
    }
}
