<?php

namespace App\Filament\Admin\Resources\Lessons;

use App\Filament\Admin\Resources\Lessons\Schemas\LessonForm;
use App\Filament\Admin\Resources\Lessons\Tables\LessonsTable;
use App\Models\Lesson;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LessonResource extends Resource
{
    protected static ?string $model = Lesson::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.lessons');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.lesson');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.lessons');
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
        return LessonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LessonsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'view' => Pages\ViewLesson::route('/{record}'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        $query = app(CourseAccessService::class)->scopeForInstructor($query, $user, 'curriculumSection.courseGroup.instructors');

        return $query->with(['course', 'curriculumSection']);
    }
}
