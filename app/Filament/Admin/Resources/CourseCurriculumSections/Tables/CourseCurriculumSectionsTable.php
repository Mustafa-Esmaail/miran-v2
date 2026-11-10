<?php

namespace App\Filament\Admin\Resources\CourseCurriculumSections\Tables;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseCurriculumSectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
                TextColumn::make('course.slug')->label(__('admin.labels.course'))->searchable(),
                TextColumn::make('course_title')
                    ->label(__('admin.labels.course_title'))
                    ->state(fn ($record) => $record->course?->getTranslation('title', app()->getLocale()) ?? '-')
                    ->toggleable(),
                TextColumn::make('group_title')
                    ->label(__('admin.labels.group'))
                    ->state(fn ($record) => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-')
                    ->toggleable(),
                TextColumn::make('title')
                    ->label(__('admin.labels.title'))
                    ->state(fn ($record) => $record->getTranslation('title', app()->getLocale()))
                    ->searchable(),
                TextColumn::make('sort_order')->label(__('admin.labels.sort_order'))->sortable(),
                TextColumn::make('lessons_count')->label(__('admin.labels.lessons'))->counts('lessons')->toggleable(),
                TextColumn::make('sessions_count')->label(__('admin.labels.sessions'))->counts('sessions')->toggleable(),
                TextColumn::make('attachments_count')->label(__('admin.labels.attachments'))->counts('attachments')->toggleable(),
            ])
            ->filters([
                Filter::make('course_group')
                    ->label(__('admin.labels.group'))
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
                            ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null)),
                        Select::make('course_group_id')
                            ->label(__('admin.labels.group'))
                            ->options(function (Get $get) {
                                $query = CourseGroup::query();

                                if ($courseId = $get('course_id')) {
                                    $query->where('course_id', $courseId);
                                }

                                $query = app(CourseAccessService::class)->scopeForInstructor($query, Filament::auth()->user(), 'instructors');

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
                            ->when($data['course_id'] ?? null, fn (Builder $q, $courseId) => $q->where('course_id', $courseId))
                            ->when($data['course_group_id'] ?? null, fn (Builder $q, $groupId) => $q->where('course_group_id', $groupId));
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
            ])
            ->recordActions([
                ViewAction::make()->label(__('admin.pages.view')),
                EditAction::make()->label(__('admin.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
                ]),
            ])
            ->defaultSort('sort_order');
    }
}
