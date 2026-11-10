<?php

namespace App\Filament\Admin\Resources\CourseQuestions;

use App\Filament\Admin\Resources\CourseQuestions\Schemas\CourseQuestionForm;
use App\Filament\Admin\Resources\CourseQuestions\Tables\CourseQuestionsTable;
use App\Models\CourseQuestion;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseQuestionResource extends Resource
{
    protected static ?string $model = CourseQuestion::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.course_questions');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.course_question');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.course_questions');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_curriculum_group');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseQuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseQuestionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['course', 'user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseQuestions::route('/'),
            'view' => Pages\ViewCourseQuestion::route('/{record}'),
            'edit' => Pages\EditCourseQuestion::route('/{record}/edit'),
        ];
    }
}
