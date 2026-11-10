<?php

namespace App\Filament\Admin\Resources\CourseGroups;

use App\Filament\Admin\Resources\CourseGroups\Schemas\CourseGroupForm;
use App\Filament\Admin\Resources\CourseGroups\Tables\CourseGroupsTable;
use App\Models\CourseGroup;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CourseGroupResource extends Resource
{
    protected static ?string $model = CourseGroup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.course_groups');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.course_group');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.course_groups');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_delivery_group');
    }

    public static function form(Schema $schema): Schema
    {
        return CourseGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseGroups::route('/'),
            'create' => Pages\CreateCourseGroup::route('/create'),
            'view' => Pages\ViewCourseGroup::route('/{record}'),
            'edit' => Pages\EditCourseGroup::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        $query = app(CourseAccessService::class)->scopeForInstructor($query, $user, 'instructors');

        return $query->with(['course', 'instructors'])->withCount('enrollments');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        if (auth('admin')->user()?->hasRole(['admin', 'super-admin'])) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query;
    }
}
