<?php

namespace App\Filament\Admin\Resources\Assignments\Schemas;

use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.assignment_details'))
                ->columnSpanFull()
                ->schema([
                    Select::make('course_id')
                        ->label(__('admin.labels.course'))
                        ->options(fn () => Course::query()
                            ->tap(fn (Builder $query) => app(CourseAccessService::class)->scopeForInstructor($query, Filament::auth()->user(), 'groups.instructors'))
                            ->get()
                            ->mapWithKeys(fn (Course $course) => [
                                $course->id => $course->getTranslation('title', app()->getLocale()),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('course_group_id', null);
                            $set('course_curriculum_section_id', null);
                        })
                        ->required(),
                    Select::make('course_group_id')
                        ->label(__('admin.labels.group'))
                        ->helperText(__('admin.helpers.assignment_no_group'))
                        ->options(function (Get $get) {
                            $courseId = $get('course_id');
                            if (! $courseId) {
                                return [];
                            }

                            $query = CourseGroup::query()->where('course_id', $courseId);
                            $query = app(CourseAccessService::class)->scopeForInstructor($query, Filament::auth()->user(), 'instructors');

                            return $query
                                ->get()
                                ->mapWithKeys(fn (CourseGroup $group) => [
                                    $group->id => $group->getTranslation('title', app()->getLocale()) ?? ('Group #'.$group->id),
                                ])
                                ->all();
                        })
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_curriculum_section_id', null))
                        ->preload()
                        ->searchable(),
                    Select::make('course_curriculum_section_id')
                        ->label(__('admin.labels.curriculum_section'))
                        ->options(function (Get $get) {
                            $courseId = $get('course_id');
                            if (! $courseId) {
                                return [];
                            }

                            return CourseCurriculumSection::query()
                                ->where('course_id', $courseId)
                                ->where('course_group_id', $get('course_group_id'))
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (CourseCurriculumSection $section) => [
                                    $section->id => $section->getTranslation('title', app()->getLocale()),
                                ])
                                ->all();
                        })
                        ->required()
                        ->preload()
                        ->searchable(),
                    TextInput::make('title_ar')
                        ->label(__('admin.labels.title').' (AR)')
                        ->required(),
                    TextInput::make('title_en')
                        ->label(__('admin.labels.title').' (EN)'),
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
                        ->acceptedFileTypes([
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'text/csv',
                            'application/csv',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'image/*',
                            'application/pdf',
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
