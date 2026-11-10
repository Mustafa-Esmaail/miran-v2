<?php

namespace App\Filament\Admin\Resources\Certificates\Tables;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CertificatesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label(__('admin.labels.id'))->sortable()->toggleable(),
            TextColumn::make('user.name')->label(__('admin.labels.trainee'))->searchable(),
            TextColumn::make('user.email')->label(__('admin.labels.email'))->searchable()->toggleable(),
            TextColumn::make('course.slug')->label(__('admin.labels.course'))->searchable(),
            TextColumn::make('courseGroup.title')
                ->label(__('admin.labels.group'))
                ->formatStateUsing(fn ($state, Certificate $record) => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-')
                ->toggleable(),
            TextColumn::make('status')->label(__('admin.labels.status'))
                ->formatStateUsing(function ($state) {
                    $value = $state instanceof \BackedEnum ? $state->value : $state;
                    $key = 'admin.options.'.$value;

                    return __($key) !== $key ? __($key) : (string) $value;
                })->badge()->sortable(),
            TextColumn::make('approved_by')->label(__('admin.labels.approved_by'))->toggleable(),
            TextColumn::make('approved_at')->dateTime('Y-m-d H:i')->sortable()->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')
                ->options(fn (): array => Certificate::query()
                    ->distinct()
                    ->get(['status'])
                    ->mapWithKeys(fn (Certificate $certificate): array => [
                        $certificate->status->value => $certificate->status->value,
                    ])
                    ->all())->searchable(),
            Filter::make('course_group')
                ->form([
                    Select::make('course_id')
                        ->label(__('admin.labels.course'))
                        ->options(fn (): array => Course::query()
                            ->tap(fn (Builder $q) => app(CourseAccessService::class)->scopeCourses($q, Filament::auth()->user(), 'courses.viewAny'))
                            ->get()
                            ->mapWithKeys(fn (Course $c): array => [
                                $c->id => $c->getTranslation('title', app()->getLocale()),
                            ])
                            ->all()
                        )
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null)),
                    Select::make('course_group_id')
                        ->label(__('admin.labels.group'))
                        ->options(fn (Get $get): array => CourseGroup::query()
                            ->when(
                                $get('course_id'),
                                fn (Builder $q, int|string $courseId) => $q->where('course_id', $courseId)
                            )
                            ->get()
                            ->mapWithKeys(fn (CourseGroup $g): array => [
                                $g->id => $g->getTranslation('title', app()->getLocale()),
                            ])
                            ->all()
                        )
                        ->searchable(),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['course_id'] ?? null,
                            fn (Builder $q, int|string $courseId) => $q->where('course_id', $courseId)
                        )
                        ->when(
                            $data['course_group_id'] ?? null,
                            fn (Builder $q, int|string $groupId) => $q->where('course_group_id', $groupId)
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['course_id'] ?? null) {
                        $course = Course::find($data['course_id']);
                        if ($course) {
                            $indicators[] = __('admin.labels.course').': '.$course->getTranslation('title', app()->getLocale());
                        }
                    }
                    if ($data['course_group_id'] ?? null) {
                        $group = CourseGroup::find($data['course_group_id']);
                        if ($group) {
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
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.empty_states.certificates.heading'))
            ->emptyStateDescription(__('admin.empty_states.certificates.description'));
    }
}
