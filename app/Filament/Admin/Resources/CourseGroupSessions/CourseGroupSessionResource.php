<?php

namespace App\Filament\Admin\Resources\CourseGroupSessions;

use App\Filament\Admin\Resources\CourseGroupSessions\RelationManagers\AbsentEnrollmentsRelationManager;
use App\Filament\Admin\Resources\CourseGroupSessions\RelationManagers\ZoomAttendancesRelationManager;
use App\Filament\Admin\Resources\CourseGroupSessions\Schemas\CourseGroupSessionForm;
use App\Filament\Admin\Resources\CourseGroupSessions\Tables\CourseGroupSessionsTable;
use App\Models\CourseGroupSession;
use App\Services\Admin\CourseAccessService;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseGroupSessionResource extends Resource
{
    protected static ?string $model = CourseGroupSession::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-video-camera';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.sessions');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.course_group_session');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.course_group_sessions');
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
        return CourseGroupSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseGroupSessionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseGroupSessions::route('/'),
            'create' => Pages\CreateCourseGroupSession::route('/create'),
            'view' => Pages\ViewCourseGroupSession::route('/{record}'),
            'edit' => Pages\EditCourseGroupSession::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ZoomAttendancesRelationManager::class,
            AbsentEnrollmentsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        $query = app(CourseAccessService::class)->scopeForInstructor($query, $user, 'courseGroup.instructors');

        return $query->with(['courseGroup.course', 'curriculumSection', 'zoomHostUser'])
            ->withCount('zoomAttendances');
    }
}
