<?php

namespace App\Filament\Admin\Resources\CourseCurriculumSections;

use App\Filament\Admin\Resources\CourseCurriculumSections\Pages\CreateCourseCurriculumSection;
use App\Filament\Admin\Resources\CourseCurriculumSections\Pages\EditCourseCurriculumSection;
use App\Filament\Admin\Resources\CourseCurriculumSections\Pages\ListCourseCurriculumSections;
use App\Filament\Admin\Resources\CourseCurriculumSections\Pages\ViewCourseCurriculumSection;
use App\Filament\Admin\Resources\CourseCurriculumSections\RelationManagers\AttachmentsRelationManager;
use App\Filament\Admin\Resources\CourseCurriculumSections\RelationManagers\LessonsRelationManager;
use App\Filament\Admin\Resources\CourseCurriculumSections\RelationManagers\SessionsRelationManager;
use App\Filament\Admin\Resources\CourseCurriculumSections\Schemas\CourseCurriculumSectionForm;
use App\Filament\Admin\Resources\CourseCurriculumSections\Tables\CourseCurriculumSectionsTable;
use App\Models\CourseCurriculumSection;
use App\Services\Admin\CourseAccessService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CourseCurriculumSectionResource extends Resource
{
    protected static ?string $model = CourseCurriculumSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.course_curriculum_sections');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.course_curriculum_section');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.course_curriculum_sections');
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
        return CourseCurriculumSectionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseCurriculumSectionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LessonsRelationManager::class,
            SessionsRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseCurriculumSections::route('/'),
            'create' => CreateCourseCurriculumSection::route('/create'),
            'view' => ViewCourseCurriculumSection::route('/{record}'),
            'edit' => EditCourseCurriculumSection::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        $query = app(CourseAccessService::class)->scopeForInstructor($query, $user, 'courseGroup.instructors');

        return $query->with(['course', 'courseGroup']);
    }
}
