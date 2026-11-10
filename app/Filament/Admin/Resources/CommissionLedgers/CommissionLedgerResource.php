<?php

namespace App\Filament\Admin\Resources\CommissionLedgers;

use App\Filament\Admin\Resources\CommissionLedgers\Pages\ListCommissionLedgers;
use App\Filament\Admin\Resources\CommissionLedgers\Pages\ViewCommissionLedger;
use App\Filament\Admin\Resources\CommissionLedgers\Tables\CommissionLedgersTable;
use App\Models\CommissionLedger;
use App\Traits\CrmRoleScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommissionLedgerResource extends Resource
{
    use CrmRoleScope;

    protected static ?string $model = CommissionLedger::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.commission_ledgers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.commission_ledger');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.commission_ledgers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_team_group');
    }

    public static function table(Table $table): Table
    {
        return CommissionLedgersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCommissionLedgers::route('/'),
            'view' => ViewCommissionLedger::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['employee', 'orderAttribution.order']);
        $employee = (new self)->currentEmployee();

        return (new self)->scopeDeals($query, $employee);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
