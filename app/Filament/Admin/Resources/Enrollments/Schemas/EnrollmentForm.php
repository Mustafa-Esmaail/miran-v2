<?php

namespace App\Filament\Admin\Resources\Enrollments\Schemas;

use App\Enums\EnrollmentStatus;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.enrollment'))
                ->columnSpanFull()
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'email')
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => trim($record->name.' - '.$record->email, ' -'))
                        ->label(__('admin.labels.trainee'))
                        ->required()
                        ->searchable(['name', 'email'])
                        ->preload(),
                    Select::make('course_id')
                        ->relationship(
                            'course',
                            'slug',
                            modifyQueryUsing: fn (Builder $query) => app(CourseAccessService::class)->scopeCourses($query, Filament::auth()->user(), 'courses.viewAny')
                        )
                        ->getOptionLabelFromRecordUsing(fn (Course $record): string => $record->getTranslation('title', app()->getLocale()))
                        ->label(__('admin.labels.course'))
                        ->required()
                        ->searchable(['title->en', 'title->ar', 'slug'])
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null)),
                    Select::make('course_group_id')
                        ->options(function (Get $get) {
                            $courseId = $get('course_id');
                            if (! $courseId) {
                                return [];
                            }

                            $query = CourseGroup::query()->where('course_id', $courseId);
                            $query = app(CourseAccessService::class)->scopeCourseRelation($query, Filament::auth()->user(), 'course', 'course_groups.viewAny');

                            return $query->get()
                                ->mapWithKeys(fn (CourseGroup $group) => [
                                    $group->id => $group->getTranslation('title', app()->getLocale()),
                                ])
                                ->all();
                        })
                        ->label(__('admin.labels.group'))
                        ->required()
                        ->unique(
                            table: Enrollment::class,
                            column: 'course_group_id',
                            ignoreRecord: true,
                            modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                ->where('user_id', $get('user_id'))
                                ->whereNull('deleted_at'),
                        )
                        ->validationMessages([
                            'unique' => __('admin.validation.enrollment_already_exists'),
                        ])
                        ->live()
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.status'))
                ->columnSpanFull()
                ->schema([
                    Select::make('status')->options([
                        EnrollmentStatus::Pending->value => __('admin.options.pending'),
                        EnrollmentStatus::Active->value => __('admin.options.active'),
                        EnrollmentStatus::Cancelled->value => __('admin.options.cancelled'),
                        EnrollmentStatus::PendingCertificate->value => __('admin.options.pending_certificate'),
                    ])->required(),
                ])
                ->columns(1),
        ]);
    }
}
