<?php

namespace App\Filament\Admin\Resources\QuizQuestions\Schemas;

use App\Enums\QuizQuestionType;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class QuizQuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.question'))
                ->columnSpanFull()
                ->schema([
                    Select::make('quiz_id')
                        ->relationship(
                            'quiz',
                            'title',
                            modifyQueryUsing: fn (Builder $query) => app(CourseAccessService::class)->scopeCourseRelation($query, Filament::auth()->user(), 'course', 'quizzes.viewAny')
                        )
                        ->label(__('admin.labels.quiz'))
                        ->default(fn () => request()->integer('quiz_id') ?: null)
                        ->disabled(fn () => request()->integer('quiz_id'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    Textarea::make('question')
                        ->required()
                        ->rows(3),
                    Select::make('type')
                        ->options([
                            QuizQuestionType::Mcq->value => __('admin.options.multiple_choice'),
                            QuizQuestionType::TrueFalse->value => __('admin.options.true_false'),
                        ])
                        ->required(),
                    TextInput::make('points')
                        ->numeric()
                        ->minValue(1)
                        ->default(1)
                        ->required(),
                    Repeater::make('options')
                        ->relationship()
                        ->schema([
                            TextInput::make('label')
                                ->required()
                                ->maxLength(255),
                            Toggle::make('is_correct')
                                ->label(__('admin.labels.correct'))
                                ->default(false),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->defaultItems(2)
                        ->helperText(__('admin.helpers.mark_one_or_more_correct_options')),
                ])
                ->columns(2),
        ]);
    }
}
