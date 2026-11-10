<?php

namespace App\Filament\Admin\Resources\Courses;

use App\Filament\Admin\Resources\Courses\Schemas\CourseForm;
use App\Filament\Admin\Resources\Courses\Tables\CoursesTable;
use App\Models\Course;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.courses');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.course');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.courses');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_catalog_group');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoursesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        $query = app(CourseAccessService::class)->scopeForInstructor($query, $user, 'groups.instructors');

        return $query->with(['category', 'discounts'])->withCount(['groups', 'curriculumSections', 'lessons', 'enrollments']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        if (auth('admin')->user()?->hasRole(['admin', 'super-admin'])) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CurriculumSectionsRelationManager::class,
            RelationManagers\AssignmentsRelationManager::class,
            RelationManagers\GroupsRelationManager::class,
            RelationManagers\LessonsRelationManager::class,
            RelationManagers\AttachmentsRelationManager::class,
            RelationManagers\SessionsRelationManager::class,
            RelationManagers\QuizzesRelationManager::class,
            RelationManagers\EnrollmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'view' => Pages\ViewCourse::route('/{record}'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
