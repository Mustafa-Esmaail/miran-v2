<?php

namespace App\Filament\Admin\Resources\CourseGroups\Schemas;

use App\Filament\Forms\TranslatableField;
use App\Models\Course;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CourseGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([

            Section::make(__('admin.sections.group_details'))
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
                        ->searchable(['title->en', 'title->ar', 'slug']),
                    TranslatableField::localeSwitcher(),
                    TranslatableField::text('title', __('admin.fields.title'), true),
                    TextInput::make('group_price_amount')
                        ->numeric()
                        ->label(__('admin.labels.group_price'))
                        ->prefix('SAR'),
                    TextInput::make('capacity')->numeric()->helperText(__('admin.helpers.total_seats_available')),
                ])
                ->columns(2),
            Section::make(__('admin.sections.status'))
                ->columnSpanFull()
                ->schema([
                    Select::make('status')->options([
                        'open' => __('admin.options.open'),
                        'closed' => __('admin.options.closed'),
                        'archived' => __('admin.options.archived'),
                    ])->required(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.schedule'))
                ->columnSpanFull()
                ->schema([
                    DateTimePicker::make('start_at')
                        ->seconds(false),
                    DateTimePicker::make('end_at')
                        ->seconds(false),
                    DateTimePicker::make('enrollment_deadline_at')
                        ->seconds(false)->label(__('admin.labels.enrollment_deadline')),
                ])
                ->columns(3),
            Section::make(__('admin.sections.instructors'))
                ->columnSpanFull()
                ->schema([
                    Select::make('instructors')
                        ->relationship(
                            'instructors',
                            'name',
                            modifyQueryUsing: fn (Builder $query) => $query
                                ->whereHas('roles', fn (Builder $q) => $q->where('name', 'instructor'))
                        )
                        ->label(__('admin.labels.instructors'))
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                ])
                ->columns(1),
        ]);
    }
}
