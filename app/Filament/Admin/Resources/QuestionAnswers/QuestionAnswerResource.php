<?php

namespace App\Filament\Admin\Resources\QuestionAnswers;

use App\Filament\Admin\Resources\QuestionAnswers\Schemas\QuestionAnswerForm;
use App\Filament\Admin\Resources\QuestionAnswers\Tables\QuestionAnswersTable;
use App\Models\QuestionAnswer;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuestionAnswerResource extends Resource
{
    protected static ?string $model = QuestionAnswer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.question_answers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.question_answer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.question_answers');
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
        return QuestionAnswerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionAnswersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['question.course', 'user']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestionAnswers::route('/'),
            'view' => Pages\ViewQuestionAnswer::route('/{record}'),
            'edit' => Pages\EditQuestionAnswer::route('/{record}/edit'),
        ];
    }
}
