<?php

namespace App\Filament\Admin\Resources\Assignments;

use App\Filament\Admin\Resources\Assignments\RelationManagers\SubmissionsRelationManager;
use App\Filament\Admin\Resources\Assignments\Schemas\AssignmentForm;
use App\Filament\Admin\Resources\Assignments\Tables\AssignmentsTable;
use App\Models\Assignment;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AssignmentResource extends Resource
{
    protected static ?string $model = Assignment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.assignments');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.assignment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.assignments');
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
        return AssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssignmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssignments::route('/'),
            'create' => Pages\CreateAssignment::route('/create'),
            'view' => Pages\ViewAssignment::route('/{record}'),
            'edit' => Pages\EditAssignment::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SubmissionsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if ($user->hasRole('instructor')) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereHas('courseGroup.instructors', fn (Builder $b) => $b->whereKey($user->id))
                    ->orWhereHas('course.groups.instructors', fn (Builder $b) => $b->whereKey($user->id));
            });
        }

        return $query->with(['course', 'courseGroup', 'curriculumSection'])->withCount('submissions');
    }
}
