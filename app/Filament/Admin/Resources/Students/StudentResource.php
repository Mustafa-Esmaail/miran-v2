<?php

namespace App\Filament\Admin\Resources\Students;

use App\Filament\Admin\Resources\Customers\RelationManagers\CertificatesRelationManager;
use App\Filament\Admin\Resources\Customers\RelationManagers\EnrollmentsRelationManager;
use App\Filament\Admin\Resources\Customers\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\Students\Schemas\StudentForm;
use App\Filament\Admin\Resources\Students\Tables\StudentsTable;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.students');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.student');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.students');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_people_group');
    }

    public static function canViewAny(): bool
    {
        $user = auth('admin')->user();

        if (! $user) {
            return false;
        }

        return $user->can('students.viewAny') || $user->can('users.viewAny');
    }

    public static function form(Schema $schema): Schema
    {
        return StudentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StudentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
            EnrollmentsRelationManager::class,
            CertificatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view' => Pages\ViewStudent::route('/{record}'),
            'edit' => Pages\EditStudent::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(fn (Builder $query) => $query
                ->whereHas('enrollments')
                ->orWhereHas('invitedSeats')
            )
            ->withCount('enrollments');
    }
}
