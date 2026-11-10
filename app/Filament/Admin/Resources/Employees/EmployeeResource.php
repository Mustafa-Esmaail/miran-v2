<?php

namespace App\Filament\Admin\Resources\Employees;

use App\Filament\Admin\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Admin\Resources\Employees\Pages\EditEmployee;
use App\Filament\Admin\Resources\Employees\Pages\ListEmployees;
use App\Filament\Admin\Resources\Employees\Pages\ViewEmployee;
use App\Filament\Admin\Resources\Employees\RelationManagers\CrmActivitiesRelationManager;
use App\Filament\Admin\Resources\Employees\RelationManagers\CrmTasksRelationManager;
use App\Filament\Admin\Resources\Employees\RelationManagers\ReferredOrdersRelationManager;
use App\Filament\Admin\Resources\Employees\RelationManagers\ServiceRequestsRelationManager;
use App\Filament\Admin\Resources\Employees\RelationManagers\ShareLinksRelationManager;
use App\Filament\Admin\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\Admin\Resources\Employees\Tables\EmployeesTable;
use App\Models\Employee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.employees');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.employee');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.employees');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_team_group');
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReferredOrdersRelationManager::class,
            ServiceRequestsRelationManager::class,
            CrmActivitiesRelationManager::class,
            CrmTasksRelationManager::class,
            ShareLinksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view' => ViewEmployee::route('/{record}'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['department', 'team', 'user']);
    }
}
