<?php

namespace App\Filament\Admin\Resources\CourseGroupSessions\Tables;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\CourseGroupSession;
use App\Services\Admin\CourseAccessService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseGroupSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('courseGroup.id')->label(__('admin.labels.group'))->sortable(),
            TextColumn::make('course_group_title')
                ->label(__('admin.labels.group_title'))
                ->state(fn (CourseGroupSession $record) => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-')
                ->searchable(query: fn (Builder $query, string $search): Builder => self::applyCourseGroupTitleSearch($query, $search))
                ->toggleable(),
            TextColumn::make('curriculum_section_title')
                ->label(__('admin.labels.curriculum_section'))
                ->state(fn (CourseGroupSession $record) => $record->curriculumSection?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('course_title')
                ->label(__('admin.labels.course'))
                ->state(fn (CourseGroupSession $record) => $record->courseGroup?->course?->getTranslation('title', app()->getLocale()) ?? '-')
                ->searchable(query: fn (Builder $query, string $search): Builder => self::applyCourseTitleSearch($query, $search)),
            TextColumn::make('title')->searchable(),
            TextColumn::make('start_at')->dateTime('Y-m-d H:i')->sortable(),
            TextColumn::make('zoom_host_display')
                ->label(__('admin.labels.zoom_host'))
                ->state(fn (CourseGroupSession $record): string => $record->zoomHostUser?->name ?? ($record->zoom_host_email ?: '-'))
                ->toggleable(),
            TextColumn::make('duration_minutes')
                ->label(__('admin.labels.duration'))
                ->formatStateUsing(fn ($state) => $state ? $state.' min' : '-')
                ->sortable(),
            TextColumn::make('zoom_meeting_id')
                ->label(__('admin.labels.zoom_meeting_id'))
                ->placeholder('-')
                ->toggleable(),
            TextColumn::make('zoomAttendances_count')
                ->label(__('admin.labels.zoom_attendance_count'))
                ->counts('zoomAttendances')
                ->toggleable(),
            TextColumn::make('attachments')
                ->label(__('admin.labels.attachments'))
                ->state(fn (CourseGroupSession $record) => count($record->attachments ?? []))
                ->toggleable(),
            TextColumn::make('join_url')
                ->label(__('admin.labels.join_url'))
                ->formatStateUsing(fn (CourseGroupSession $record): string => filled($record->join_url) ? __('admin.actions.join') : '-')
                ->badge()
                ->color(fn (CourseGroupSession $record): string => filled($record->join_url) ? 'success' : 'gray')
                ->icon(fn (CourseGroupSession $record): ?string => filled($record->join_url) ? 'heroicon-o-arrow-top-right-on-square' : null)
                ->placeholder('-')
                ->url(fn (CourseGroupSession $record): ?string => filled($record->join_url) ? $record->join_url : null)
                ->openUrlInNewTab()
                ->toggleable(),
            TextColumn::make('zoom_start_url')
                ->label(__('admin.labels.zoom_start_url'))
                ->formatStateUsing(fn (CourseGroupSession $record): string => filled($record->zoom_start_url) ? __('admin.actions.start_meeting') : '-')
                ->badge()
                ->color(fn (CourseGroupSession $record): string => filled($record->zoom_start_url) ? 'warning' : 'gray')
                ->icon(fn (CourseGroupSession $record): ?string => filled($record->zoom_start_url) ? 'heroicon-o-video-camera' : null)
                ->placeholder('-')
                ->url(fn (CourseGroupSession $record): ?string => filled($record->zoom_start_url) ? $record->zoom_start_url : null)
                ->openUrlInNewTab()
                ->toggleable(),
            TextColumn::make('recording_url')
                ->label(__('admin.labels.recording'))
                ->formatStateUsing(fn (CourseGroupSession $record): string => filled($record->recording_url) ? __('admin.actions.watch_recording') : '-')
                ->badge()
                ->color(fn (CourseGroupSession $record): string => filled($record->recording_url) ? 'info' : 'gray')
                ->icon(fn (CourseGroupSession $record): ?string => filled($record->recording_url) ? 'heroicon-o-play-circle' : null)
                ->placeholder('-')
                ->url(fn (CourseGroupSession $record): ?string => filled($record->recording_url) ? $record->recording_url : null)
                ->openUrlInNewTab()
                ->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('updated_at')->label(__('admin.labels.updated_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Filter::make('course_group')
                ->label(__('admin.labels.group'))
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
                        ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null)),
                    Select::make('course_group_id')
                        ->label(__('admin.labels.group'))
                        ->options(function (Get $get) {
                            $query = CourseGroup::query();

                            if ($courseId = $get('course_id')) {
                                $query->where('course_id', $courseId);
                            }

                            $query = app(CourseAccessService::class)->scopeCourseRelation($query, Filament::auth()->user(), 'course', 'sessions.viewAny');

                            return $query
                                ->get()
                                ->mapWithKeys(fn (CourseGroup $group) => [
                                    $group->id => $group->getTranslation('title', app()->getLocale()) ?? ('Group #'.$group->id),
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->preload(),
                ])

                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['course_id'] ?? null,
                            fn (Builder $q, $courseId) => $q->whereHas('courseGroup', fn (Builder $b) => $b->where('course_id', $courseId))
                        )
                        ->when(
                            $data['course_group_id'] ?? null,
                            fn (Builder $q, $groupId) => $q->where('course_group_id', $groupId)
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if ($courseId = $data['course_id'] ?? null) {
                        if ($course = Course::query()->find($courseId)) {
                            $indicators[] = __('admin.labels.course').': '.$course->getTranslation('title', app()->getLocale());
                        }
                    }

                    if ($groupId = $data['course_group_id'] ?? null) {
                        if ($group = CourseGroup::query()->find($groupId)) {
                            $indicators[] = __('admin.labels.group').': '.$group->getTranslation('title', app()->getLocale());
                        }
                    }

                    return $indicators;
                }),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('start_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.course_group_sessions.heading'))
            ->emptyStateDescription(__('admin.empty_states.course_group_sessions.description'));
    }

    public static function applyCourseTitleSearch(Builder $query, string $search): Builder
    {
        return $query->whereHas('courseGroup.course', function (Builder $builder) use ($search): void {
            $builder
                ->where('title->en', 'like', "%{$search}%")
                ->orWhere('title->ar', 'like', "%{$search}%");
        });
    }

    public static function applyCourseGroupTitleSearch(Builder $query, string $search): Builder
    {
        return $query->whereHas('courseGroup', function (Builder $builder) use ($search): void {
            $builder
                ->where('title->en', 'like', "%{$search}%")
                ->orWhere('title->ar', 'like', "%{$search}%");
        });
    }
}
