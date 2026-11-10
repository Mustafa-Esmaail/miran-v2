<?php

namespace App\Filament\Admin\Resources\Deals;

use App\Filament\Admin\Resources\Deals\Pages\CreateDeal;
use App\Filament\Admin\Resources\Deals\Pages\EditDeal;
use App\Filament\Admin\Resources\Deals\Pages\ListDeals;
use App\Filament\Admin\Resources\Deals\Pages\ViewDeal;
use App\Filament\Admin\Resources\Deals\RelationManagers\CrmActivitiesRelationManager;
use App\Filament\Admin\Resources\Deals\RelationManagers\CrmTasksRelationManager;
use App\Filament\Admin\Resources\Deals\RelationManagers\QuotesRelationManager;
use App\Filament\Admin\Resources\Deals\Schemas\DealForm;
use App\Filament\Admin\Resources\Deals\Tables\DealsTable;
use App\Models\Deal;
use App\Traits\CrmRoleScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DealResource extends Resource
{
    use CrmRoleScope;

    protected static ?string $model = Deal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 11;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.deals');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.deal');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.deals');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_sales_pipeline_group');
    }

    public static function form(Schema $schema): Schema
    {
        return DealForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DealsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            QuotesRelationManager::class,
            CrmActivitiesRelationManager::class,
            CrmTasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeals::route('/'),
            'create' => CreateDeal::route('/create'),
            'view' => ViewDeal::route('/{record}'),
            'edit' => EditDeal::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['company', 'employee']);
        $employee = (new self)->currentEmployee();

        return (new self)->scopeDeals($query, $employee);
    }
}
