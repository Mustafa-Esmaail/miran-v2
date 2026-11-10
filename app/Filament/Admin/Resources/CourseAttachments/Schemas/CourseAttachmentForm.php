<?php

namespace App\Filament\Admin\Resources\CourseAttachments\Schemas;

use App\Enums\AttachmentVisibility;
use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CourseAttachmentForm
{
    public static function configure(Schema $schema): Schema
    {
        $locale = app()->getLocale();
        $user = Filament::auth()->user();

        $fileUploadConfig = fn (FileUpload $upload): FileUpload => $upload
            ->disk('s3')
            ->directory('course-attachments')
            ->visibility('private')
            ->openable()
            ->getUploadedFileNameForStorageUsing(
                fn (TemporaryUploadedFile $file): string => now()->format('Ymd_His').'_'.str_replace(' ', '_', $file->getClientOriginalName())
            );

        return $schema->schema([
            Section::make(__('admin.sections.attachment'))
                ->columnSpanFull()
                ->schema([

                    // Step 1: Course (virtual, not saved — drives group dropdown)
                    Select::make('course_id')
                        ->label(__('admin.labels.course'))
                        ->options(fn () => Course::query()
                            ->tap(fn (Builder $q) => app(CourseAccessService::class)->scopeCourses($q, $user, 'courses.viewAny'))
                            ->get()
                            ->mapWithKeys(fn (Course $c): array => [
                                $c->id => $c->getTranslation('title', $locale),
                            ])
                            ->all()
                        )
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null)),

                    // Step 2: Group (filtered by course, reactive, drives section dropdown). Not required for profile attachments, hidden for general ones.
                    Select::make('course_group_id')
                        ->label(__('admin.labels.group'))
                        ->options(fn (Get $get) => CourseGroup::query()
                            ->where('course_id', $get('course_id'))
                            ->get()
                            ->mapWithKeys(fn (CourseGroup $g): array => [
                                $g->id => $g->getTranslation('title', $locale),
                            ])
                            ->all()
                        )
                        ->required(fn (Get $get): bool => AttachmentVisibility::sectionRequiredFor($get('visibility')))
                        ->visible(fn (Get $get): bool => $get('visibility') !== AttachmentVisibility::General->value)
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_curriculum_section_id', null)),

                    // Step 3: Day / Section (filtered by group). Not required for profile attachments, hidden for general ones.
                    Select::make('course_curriculum_section_id')
                        ->label(__('admin.labels.curriculum_section'))
                        ->options(fn (Get $get) => CourseCurriculumSection::query()
                            ->where('course_group_id', $get('course_group_id'))
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (CourseCurriculumSection $s): array => [
                                $s->id => $s->getTranslation('title', $locale),
                            ])
                            ->all()
                        )
                        ->required(fn (Get $get): bool => AttachmentVisibility::sectionRequiredFor($get('visibility')))
                        ->visible(fn (Get $get): bool => $get('visibility') !== AttachmentVisibility::General->value)
                        ->searchable()
                        ->live(),

                    Select::make('visibility')
                        ->options([
                            AttachmentVisibility::Public->value => __('admin.options.public'),
                            AttachmentVisibility::EnrolledOnly->value => __('admin.options.enrolled_only'),
                            AttachmentVisibility::Profile->value => __('admin.options.profile'),
                            AttachmentVisibility::General->value => __('admin.options.general'),
                        ])
                        ->live()
                        ->required(),

                    TextInput::make('title_ar')
                        ->label(__('admin.labels.title').' (AR)')
                        ->required(),

                    TextInput::make('title_en')
                        ->label(__('admin.labels.title').' (EN)'),

                    $fileUploadConfig(
                        FileUpload::make('file_path')
                            ->label(__('admin.labels.files'))
                            ->multiple()
                            ->required()
                    )->columnSpanFull(),
                ])
                ->columns(3),
        ]);
    }
}
