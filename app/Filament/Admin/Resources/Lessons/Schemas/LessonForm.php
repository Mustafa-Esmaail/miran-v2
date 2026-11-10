<?php

namespace App\Filament\Admin\Resources\Lessons\Schemas;

use App\Enums\LessonType;
use App\Filament\Forms\TranslatableField;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.content'))
                ->columnSpanFull()
                ->schema([
                    Select::make('course_id')
                        ->relationship(
                            'course',
                            'slug',
                            modifyQueryUsing: fn (Builder $query) => app(CourseAccessService::class)->scopeCourses($query, Filament::auth()->user(), 'courses.viewAny')
                        )
                        ->getOptionLabelFromRecordUsing(fn (Course $record): string => $record->getTranslation('title', app()->getLocale()))
                        ->label(__('admin.labels.course'))
                        ->required()
                        ->preload()
                        ->searchable(['title->en', 'title->ar', 'slug'])
                        ->live(),
                    Select::make('course_curriculum_section_id')
                        ->label(__('admin.labels.curriculum_section'))
                        ->options(function (Get $get) {
                            $courseId = $get('course_id');
                            if (! $courseId) {
                                return [];
                            }

                            return CourseCurriculumSection::query()
                                ->where('course_id', $courseId)
                                ->orderBy('sort_order')
                                ->get()
                                ->mapWithKeys(fn (CourseCurriculumSection $section) => [
                                    $section->id => $section->getTranslation('title', app()->getLocale()),
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->required(),
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true),
                    TranslatableField::rich('content', __('admin.fields.content'))->columnSpanFull(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.settings'))
                ->columnSpanFull()
                ->schema([
                    Select::make('type')
                        ->options([
                            LessonType::Pdf->value => __('admin.options.pdf'),
                            LessonType::Text->value => __('admin.options.text'),
                            LessonType::Video->value => __('admin.options.video'),
                        ])
                        ->required(),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->nullable()
                        ->helperText(__('admin.helpers.lower_numbers_appear_first')),
                    Toggle::make('is_preview')->label(__('admin.labels.preview')),
                ])
                ->columns(2),
            Section::make(__('admin.sections.media'))
                ->columnSpanFull()
                ->schema([
                    FileUpload::make('pdf_path')
                        ->disk('s3')
                        ->directory('lesson-pdfs')
                        ->visibility('private')
                        ->helperText(__('admin.helpers.upload_for_pdf_lessons')),
                    TextInput::make('video_url')
                        ->url()
                        ->label(__('admin.labels.video_url'))
                        ->placeholder(__('admin.placeholders.https'))
                        ->helperText(__('admin.helpers.provide_for_video_lessons')),
                ])
                ->columns(2),
        ]);
    }
}
