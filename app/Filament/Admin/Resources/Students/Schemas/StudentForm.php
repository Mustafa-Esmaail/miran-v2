<?php

namespace App\Filament\Admin\Resources\Students\Schemas;

use App\Models\Course;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.profile'))
                ->columnSpanFull()
                ->schema([
                    TextInput::make('name')->label(__('admin.fields.name'))->required()->maxLength(255),
                    TextInput::make('email')
                        ->label(__('admin.fields.email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => __('admin.validation.email_unique'),
                        ]),
                    TextInput::make('phone')->label(__('admin.fields.phone'))->tel()->maxLength(30),
                    TextInput::make('national_id')->label(__('admin.fields.national_id'))->maxLength(50),
                    TextInput::make('nationality')->label(__('admin.fields.nationality'))->maxLength(100),
                    DatePicker::make('birth_date')->label(__('admin.fields.birth_date'))->nullable(),
                    Select::make('gender')->label(__('admin.fields.gender'))->options([
                        'male' => __('admin.options.male'),
                        'female' => __('admin.options.female'),
                    ])->nullable(),
                    Textarea::make('about')->label(__('admin.fields.about'))->rows(4)->columnSpanFull(),
                    FileUpload::make('avatar_path')
                        ->label(__('admin.fields.avatar'))
                        ->image()
                        ->disk('s3')
                        ->directory('avatars')
                        ->visibility('private')
                        ->columnSpanFull(),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->helperText(__('admin.helpers.leave_blank_to_keep_the_current_password'))
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->required(fn (string $context): bool => $context === 'create'),
                    Toggle::make('is_chat_banned')
                        ->label(__('admin.fields.chat_banned'))
                        ->helperText(__('admin.helpers.chat_banned'))
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make(__('admin.sections.enrollment'))
                ->columnSpanFull()
                ->schema([
                    Select::make('course_id')
                        ->label(__('admin.labels.course'))
                        ->options(fn () => Course::query()
                            ->tap(fn (Builder $query) => app(CourseAccessService::class)->scopeCourses($query, Filament::auth()->user(), 'courses.viewAny'))
                            ->get()
                            ->mapWithKeys(fn (Course $course) => [
                                $course->id => $course->getTranslation('title', app()->getLocale()),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('course_group_id', null))
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                    Select::make('course_group_id')
                        ->label(__('admin.labels.group'))
                        ->options(function (Get $get) {
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
                        ->searchable()
                        ->preload()
                        ->visible(fn (string $operation): bool => $operation === 'create')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                ])
                ->columns(2)
                ->visible(fn (string $operation): bool => $operation === 'create'),
        ]);
    }
}
