<?php

namespace App\Filament\Admin\Resources\QuizQuestions;

use App\Filament\Admin\Resources\QuizQuestions\Schemas\QuizQuestionForm;
use App\Filament\Admin\Resources\QuizQuestions\Tables\QuizQuestionsTable;
use App\Models\QuizQuestion;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizQuestionResource extends Resource
{
    protected static ?string $model = QuizQuestion::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.quiz_questions');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.quiz_question');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.quiz_questions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_assessments_group');
    }

    public static function form(Schema $schema): Schema
    {
        return QuizQuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizQuestionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizQuestions::route('/'),
            'create' => Pages\CreateQuizQuestion::route('/create'),
            'view' => Pages\ViewQuizQuestion::route('/{record}'),
            'edit' => Pages\EditQuizQuestion::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        $query = app(CourseAccessService::class)->scopeCourseRelation($query, $user, 'quiz.course', 'quizzes.viewAny');

        return $query->with('quiz')->withCount('options');
    }
}
