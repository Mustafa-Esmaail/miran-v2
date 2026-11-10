<?php

namespace App\Filament\Admin\Resources\Quizzes;

use App\Filament\Admin\Resources\Quizzes\Schemas\QuizForm;
use App\Filament\Admin\Resources\Quizzes\Tables\QuizzesTable;
use App\Models\Quiz;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.quizzes');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.quiz');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.quizzes');
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
        return QuizForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuizzesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'view' => Pages\ViewQuiz::route('/{record}'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\QuestionsRelationManager::class,
            RelationManagers\AttemptsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        $query = app(CourseAccessService::class)->scopeCourseRelation($query, $user, 'course', 'quizzes.viewAny');

        return $query->with(['course', 'courseGroup', 'curriculumSection'])->withCount(['questions', 'attempts']);
    }
}
