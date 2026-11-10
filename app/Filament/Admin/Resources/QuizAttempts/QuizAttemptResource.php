<?php

namespace App\Filament\Admin\Resources\QuizAttempts;

use App\Filament\Admin\Resources\QuizAttempts\Schemas\QuizAttemptForm;
use App\Filament\Admin\Resources\QuizAttempts\Tables\QuizAttemptsTable;
use App\Models\QuizAttempt;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizAttemptResource extends Resource
{
    protected static ?string $model = QuizAttempt::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.quiz_attempts');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.quiz_attempt');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.quiz_attempts');
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
        return QuizAttemptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizAttemptsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizAttempts::route('/'),
            'create' => Pages\CreateQuizAttempt::route('/create'),
            'view' => Pages\ViewQuizAttempt::route('/{record}'),
            'edit' => Pages\EditQuizAttempt::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        $query = app(CourseAccessService::class)->scopeCourseRelation($query, $user, 'quiz.course', 'quizzes.viewAny');

        return $query
            ->with(['quiz.questions', 'user'])
            ->selectRaw('quiz_attempts.*, (
                SELECT COUNT(*) FROM quiz_attempts qa2
                WHERE qa2.quiz_id = quiz_attempts.quiz_id
                  AND qa2.user_id = quiz_attempts.user_id
                  AND qa2.id <= quiz_attempts.id
            ) AS attempt_number');
    }
}
