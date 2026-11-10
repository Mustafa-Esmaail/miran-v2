<?php

namespace App\Filament\Admin\Resources\CrmTasks;

use App\Filament\Admin\Resources\CrmTasks\Pages\CreateCrmTask;
use App\Filament\Admin\Resources\CrmTasks\Pages\EditCrmTask;
use App\Filament\Admin\Resources\CrmTasks\Pages\ListCrmTasks;
use App\Filament\Admin\Resources\CrmTasks\Pages\ViewCrmTask;
use App\Filament\Admin\Resources\CrmTasks\Schemas\CrmTaskForm;
use App\Filament\Admin\Resources\CrmTasks\Tables\CrmTasksTable;
use App\Models\CrmTask;
use App\Traits\CrmRoleScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CrmTaskResource extends Resource
{
    use CrmRoleScope;

    protected static ?string $model = CrmTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.crm_tasks');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.crm_task');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.crm_tasks');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_engagement_group');
    }

    public static function form(Schema $schema): Schema
    {
        return CrmTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmTasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmTasks::route('/'),
            'create' => CreateCrmTask::route('/create'),
            'view' => ViewCrmTask::route('/{record}'),
            'edit' => EditCrmTask::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('employee');
        $employee = (new self)->currentEmployee();

        return (new self)->scopeActivities($query, $employee);
    }
}
