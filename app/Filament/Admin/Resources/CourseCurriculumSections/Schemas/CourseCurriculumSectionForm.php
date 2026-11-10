<?php

namespace App\Filament\Admin\Resources\CourseCurriculumSections\Schemas;

use App\Enums\CourseTemplate;
use App\Filament\Forms\TranslatableField;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CourseCurriculumSectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.content'))
                    ->schema([
                        Select::make('course_id')
                            ->relationship(
                                'course',
                                'slug',
                                modifyQueryUsing: fn (Builder $query) => app(CourseAccessService::class)
                                    ->scopeCourses($query, Filament::auth()->user(), 'courses.viewAny')
                            )
                            ->label(__('admin.labels.course'))
                            ->required()
                            ->preload()
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null))
                            ->columnSpanFull(),
                        Select::make('course_group_id')
                            ->label(__('admin.labels.group'))
                            ->options(function (Get $get): array {
                                $courseId = $get('course_id');
                                if (! $courseId) {
                                    return [];
                                }

                                return CourseGroup::query()
                                    ->where('course_id', $courseId)
                                    ->get()
                                    ->mapWithKeys(fn (CourseGroup $group) => [
                                        $group->id => $group->getTranslation('title', app()->getLocale()) ?? ('Group #'.$group->id),
                                    ])
                                    ->all();
                            })
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label(__('admin.labels.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpanFull(),
                        TranslatableField::localeSwitcher(),
                        TranslatableField::text('title', __('admin.fields.title'), true)->columnSpanFull(),
                        TranslatableField::rich('description', __('admin.fields.description'))->columnSpanFull(),
                    ])
                    ->columns(1),
                self::bootcampSection(fn (Get $get): bool => self::courseIsBootcamp($get('course_id'))),
            ]);
    }

    /**
     * Extra module fields used only by the bootcamp course page.
     *
     * @param  \Closure(Get): bool  $isVisible
     */
    public static function bootcampSection(\Closure $isVisible): Section
    {
        return Section::make(__('admin.bootcamp.sections.module'))
            ->description(__('admin.bootcamp.helpers.module'))
            ->visible($isVisible)
            ->schema([
                TextInput::make('week')
                    ->label(__('admin.bootcamp.fields.week'))
                    ->helperText(__('admin.bootcamp.helpers.week'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(52),
                Toggle::make('is_featured')
                    ->label(__('admin.bootcamp.fields.is_featured'))
                    ->inline(false),
                Toggle::make('is_capstone')
                    ->label(__('admin.bootcamp.fields.is_capstone'))
                    ->helperText(__('admin.bootcamp.helpers.is_capstone'))
                    ->inline(false),
                TranslatableField::text('badge', __('admin.bootcamp.fields.badge')),
                TranslatableField::text('outcome', __('admin.bootcamp.fields.outcome')),
                Repeater::make('bullets')
                    ->label(__('admin.bootcamp.fields.bullets'))
                    ->schema([
                        TextInput::make('text.ar')->label(__('admin.bootcamp.fields.text').' (AR)'),
                        TextInput::make('text.en')->label(__('admin.bootcamp.fields.text').' (EN)'),
                    ])
                    ->columns(2)
                    ->reorderable()
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    public static function courseIsBootcamp(mixed $courseId): bool
    {
        if (! $courseId) {
            return false;
        }

        return Course::query()
            ->whereKey($courseId)
            ->whereIn('template', array_map(fn (CourseTemplate $template): string => $template->value, CourseTemplate::bootcamps()))
            ->exists();
    }
}
