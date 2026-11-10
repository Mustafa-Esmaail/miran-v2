<?php

namespace App\Filament\Admin\Resources\SalesTargets;

use App\Filament\Admin\Resources\SalesTargets\Pages\CreateSalesTarget;
use App\Filament\Admin\Resources\SalesTargets\Pages\EditSalesTarget;
use App\Filament\Admin\Resources\SalesTargets\Pages\ListSalesTargets;
use App\Filament\Admin\Resources\SalesTargets\Pages\ViewSalesTarget;
use App\Filament\Admin\Resources\SalesTargets\Schemas\SalesTargetForm;
use App\Filament\Admin\Resources\SalesTargets\Tables\SalesTargetsTable;
use App\Models\SalesTarget;
use App\Traits\CrmRoleScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesTargetResource extends Resource
{
    use CrmRoleScope;

    protected static ?string $model = SalesTarget::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.sales_targets');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.sales_target');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.sales_targets');
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
        return SalesTargetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTargetsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesTargets::route('/'),
            'create' => CreateSalesTarget::route('/create'),
            'view' => ViewSalesTarget::route('/{record}'),
            'edit' => EditSalesTarget::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['employee.team', 'employee.user']);
        $employee = (new self)->currentEmployee();

        return (new self)->scopeDeals($query, $employee);
    }
}
