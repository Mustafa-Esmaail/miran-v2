<?php

namespace App\Filament\Admin\Resources\Customers;

use App\Filament\Admin\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Admin\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.customers');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.customer');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.customers');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.commerce');
    }

    public static function getNavigationItems(): array
    {
        $base = NavigationItem::make(static::getNavigationLabel())
            ->icon(static::getNavigationIcon())
            ->activeIcon(static::getActiveNavigationIcon())
            ->isActiveWhen(fn () => request()->routeIs(static::getNavigationItemActiveRoutePattern()))
            ->sort(static::getNavigationSort())
            ->url(static::getNavigationUrl());

        return [
            (clone $base)->group(__('admin.nav_groups.commerce')),
            (clone $base)->group(__('admin.nav_groups.crm')),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\OrdersRelationManager::class,
            RelationManagers\EnrollmentsRelationManager::class,
            RelationManagers\CertificatesRelationManager::class,
            RelationManagers\ServiceRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDoesntHave('roles', fn (Builder $q) => $q->where('guard_name', 'admin'))
            ->withCount(['orders', 'enrollments']);
    }
}
