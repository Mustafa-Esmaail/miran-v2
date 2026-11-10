<?php

namespace App\Filament\Admin\Resources\Quizzes\Schemas;

use App\Models\Course;
use App\Models\CourseCurriculumSection;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class QuizForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.quiz_details'))
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
                    Select::make('course_group_id')
                        ->label(__('admin.labels.course_group'))
                        ->options(function (callable $get) {
                            $courseId = $get('course_id');

                            if (! $courseId) {
                                return [];
                            }

                            return CourseGroup::query()
                                ->where('course_id', $courseId)
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (CourseGroup $group) => [
                                    $group->id => $group->getTranslation('title', app()->getLocale()),
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Select::make('course_curriculum_section_id')
                        ->label(__('admin.labels.curriculum_section'))
                        ->options(function (callable $get) {
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
                        ->preload(),
                    TextInput::make('title')->required()->placeholder(__('admin.placeholders.final_assessment')),
                ])
                ->columns(2),
            Section::make(__('admin.sections.passing_rule'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('passing_score')
                        ->numeric()
                        ->suffix('%')
                        ->helperText(__('admin.helpers.score_required_to_pass')),
                ]),
            Section::make(__('admin.sections.resubmit_settings'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('allow_resubmit')
                        ->label(__('admin.labels.allow_resubmit'))
                        ->helperText(__('admin.helpers.allow_resubmit'))
                        ->live(),
                    TextInput::make('max_attempts')
                        ->label(__('admin.labels.max_attempts'))
                        ->helperText(__('admin.helpers.max_attempts'))
                        ->numeric()
                        ->minValue(1)
                        ->nullable()
                        ->visible(fn (callable $get) => (bool) $get('allow_resubmit')),
                ])
                ->columns(2),
        ]);
    }
}
