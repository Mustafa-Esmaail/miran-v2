<?php

namespace App\Filament\Admin\Resources\CourseGroupSessions\Schemas;

use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Models\User;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CourseGroupSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.session_details'))
                ->columnSpanFull()
                ->schema([
                    Select::make('course_id')
                        ->label(__('admin.labels.course'))
                        ->options(fn () => Course::query()
                            ->tap(fn (Builder $query) => app(CourseAccessService::class)->scopeCourses($query, Filament::auth()->user(), 'sessions.viewAny'))
                            ->get()
                            ->mapWithKeys(fn (Course $course) => [
                                $course->id => $course->getTranslation('title', app()->getLocale()),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null))
                        ->afterStateHydrated(function (Set $set, ?CourseGroupSession $record): void {
                            if (! $record?->courseGroup?->course_id) {
                                return;
                            }

                            $set('course_id', $record->courseGroup->course_id);
                        })
                        ->dehydrated(false)
                        ->required(),
                    Select::make('course_group_id')
                        ->options(function (Get $get) {
                            $courseId = $get('course_id');
                            if (! $courseId) {
                                return [];
                            }

                            $query = CourseGroup::query()->where('course_id', $courseId);
                            $query = app(CourseAccessService::class)->scopeCourseRelation($query, Filament::auth()->user(), 'course', 'sessions.viewAny');

                            return $query
                                ->get()
                                ->mapWithKeys(fn (CourseGroup $group) => [
                                    $group->id => $group->getTranslation('title', app()->getLocale()) ?? ('Group #'.$group->id),
                                ])
                                ->all();
                        })
                        ->label(__('admin.labels.group'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_curriculum_section_id', null))
                        ->preload()
                        ->searchable(),
                    Select::make('course_curriculum_section_id')
                        ->label(__('admin.labels.curriculum_section'))
                        ->options(function (Get $get) {
                            $courseId = $get('course_id');
                            $groupId = $get('course_group_id');
                            if (! $courseId || ! $groupId) {
                                return [];
                            }

                            return CourseCurriculumSection::query()
                                ->where('course_id', $courseId)
                                ->where('course_group_id', $groupId)
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
                    TextInput::make('title')->placeholder(__('admin.placeholders.live_q_a_workshop_introduction')),
                    RichEditor::make('content')
                        ->label(__('admin.labels.content'))
                        ->columnSpanFull(),
                    DateTimePicker::make('start_at')
                        ->seconds(false)
                        ->required(),
                    TextInput::make('duration_minutes')
                        ->numeric()
                        ->required()
                        ->label(__('admin.labels.duration_min')),
                ])
                ->columns(2),
            Section::make(__('admin.sections.zoom'))
                ->columnSpanFull()
                ->schema([
                    Select::make('zoom_host_user_id')
                        ->label(__('admin.labels.zoom_host_user'))
                        ->options(fn () => User::query()
                            ->whereHas('roles', fn (Builder $builder) => $builder->where('name', 'instructor'))
                            ->get()
                            ->mapWithKeys(fn (User $user): array => [
                                $user->id => trim($user->name.' - '.$user->email, ' -'),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if (! $state) {
                                return;
                            }

                            $user = User::query()->find($state);
                            $set('zoom_host_email', $user?->email);
                        }),
                    TextInput::make('zoom_host_email')
                        ->label(__('admin.labels.zoom_host_email'))
                        ->email(),
                    TextInput::make('zoom_meeting_id')
                        ->label(__('admin.labels.zoom_meeting_id'))
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('zoom_meeting_uuid')
                        ->label(__('admin.labels.zoom_meeting_uuid'))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->columns(2),
            Section::make(__('admin.sections.links'))
                ->columnSpanFull()
                ->schema([
                    Textarea::make('join_url')
                        ->label(__('admin.labels.join_url'))
                        ->placeholder(__('admin.placeholders.https'))
                        ->rows(3)
                        ->rule('url'),
                    Textarea::make('recording_url')
                        ->label(__('admin.labels.recording_url'))
                        ->placeholder(__('admin.placeholders.https'))
                        ->rows(3)
                        ->rule('url'),
                    Textarea::make('zoom_start_url')
                        ->label(__('admin.labels.zoom_start_url'))
                        ->placeholder(__('admin.placeholders.https'))
                        ->rows(3)
                        ->disabled()
                        ->dehydrated(false),
                    FileUpload::make('attachments')
                        ->label(__('admin.labels.attachments'))
                        ->disk('s3')
                        ->directory('course-sessions/attachments')
                        ->visibility('private')
                        ->multiple()
                        ->reorderable()
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }
}
