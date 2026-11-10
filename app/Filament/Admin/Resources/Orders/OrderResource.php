<?php

namespace App\Filament\Admin\Resources\Orders;

use App\Filament\Admin\Resources\Orders\Schemas\OrderForm;
use App\Filament\Admin\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Traits\CrmRoleScope;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    use CrmRoleScope;

    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.orders');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.orders');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.commerce');
    }

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user', 'coupon', 'bankTransferReceipt', 'referredByEmployee', 'payments'])->withCount('items');
        $employee = (new self)->currentEmployee();

        return (new self)->scopeOrders($query, $employee);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        if (auth('admin')->user()?->hasRole(['admin', 'super-admin'])) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\Orders\RelationManagers\ItemsRelationManager::class,
            \App\Filament\Admin\Resources\Orders\RelationManagers\PaymentsRelationManager::class,
            \App\Filament\Admin\Resources\Orders\RelationManagers\BankTransferReceiptsRelationManager::class,
            \App\Filament\Admin\Resources\Orders\RelationManagers\OrderItemSeatsRelationManager::class,
            \App\Filament\Admin\Resources\Orders\RelationManagers\ActivityLogsRelationManager::class,
            \App\Filament\Admin\Resources\Orders\RelationManagers\NoticesRelationManager::class,
            // \App\Filament\Admin\Resources\Orders\RelationManagers\EnrollmentsRelationManager::class,
            // \App\Filament\Admin\Resources\Orders\RelationManagers\OrderAttributionsRelationManager::class,
            // \App\Filament\Admin\Resources\Orders\RelationManagers\CrmActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
