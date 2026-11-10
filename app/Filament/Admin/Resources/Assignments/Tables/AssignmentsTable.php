<?php

namespace App\Filament\Admin\Resources\Assignments\Tables;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignmentsTable
{
    public static function configure(Table $table): Table
    {
        $locale = app()->getLocale();

        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('course_title')
                ->label(__('admin.labels.course'))
                ->state(fn (Assignment $record) => $record->course?->getTranslation('title', $locale) ?? '-'),
            TextColumn::make('group_title')
                ->label(__('admin.labels.group'))
                ->state(fn (Assignment $record) => $record->courseGroup?->getTranslation('title', $locale) ?? __('admin.labels.all_groups'))
                ->toggleable(),
            TextColumn::make('curriculum_section_title')
                ->label(__('admin.labels.curriculum_section'))
                ->state(fn (Assignment $record) => $record->curriculumSection?->getTranslation('title', $locale) ?? '-')
                ->toggleable(),
            TextColumn::make('title')
                ->label(__('admin.labels.title'))
                ->state(fn (Assignment $record) => $record->getTranslation('title', $locale))
                ->searchable(),
            TextColumn::make('due_at')
                ->label(__('admin.labels.due_at'))
                ->dateTime('Y-m-d H:i')
                ->placeholder('-')
                ->sortable(),
            TextColumn::make('max_grade')
                ->label(__('admin.labels.max_grade'))
                ->sortable(),
            TextColumn::make('submissions_count')
                ->label(__('admin.labels.submissions_count'))
                ->counts('submissions')
                ->toggleable(),
            IconColumn::make('is_published')
                ->label(__('admin.labels.published'))
                ->boolean(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->filters([
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
                }),
        ])->actions([
            \Filament\Actions\ViewAction::make()->label(__('admin.pages.view')),
            \Filament\Actions\EditAction::make()->label(__('admin.actions.edit')),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.assignments.heading'))
            ->emptyStateDescription(__('admin.empty_states.assignments.description'));
    }
}
