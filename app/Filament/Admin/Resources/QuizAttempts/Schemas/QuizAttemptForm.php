<?php

namespace App\Filament\Admin\Resources\QuizAttempts\Schemas;

use App\Models\User;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class QuizAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make(__('admin.sections.attempt'))
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
                    Select::make('user_id')
                        ->relationship('user', 'email')
                        ->getOptionLabelFromRecordUsing(fn (User $record): string => $record->email ?? $record->name ?? $record->phone ?? (string) $record->id)
                        ->label(__('admin.labels.user'))
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('score')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                    Textarea::make('answers')
                        ->label(__('admin.labels.answers_json'))
                        ->rows(8)
                        ->formatStateUsing(function ($state) {
                            if ($state === null) {
                                return '';
                            }

                            return json_encode($state, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                        })
                        ->dehydrateStateUsing(function ($state) {
                            $trimmed = trim((string) $state);
                            if ($trimmed === '') {
                                return null;
                            }

                            $decoded = json_decode($trimmed, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                return $decoded;
                            }

                            return ['value' => $state];
                        }),
                ])
                ->columns(2),
        ]);
    }
}
