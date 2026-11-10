<?php

namespace App\Filament\Admin\Resources\Coupons;

use App\Enums\CouponTargetType;
use App\Filament\Admin\Resources\Coupons\Schemas\CouponForm;
use App\Filament\Admin\Resources\Coupons\Tables\CouponsTable;
use App\Models\Coupon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.coupons');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.coupon');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.coupons');
    }

    public static function getNavigationGroup(): ?string
    {

        return __('admin.nav_groups.commerce');
    }

    public static function form(Schema $schema): Schema
    {
        return CouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('redemptions')->with('employee');
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery();

        if (auth('admin')->user()?->hasRole(['admin', 'super-admin'])) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'view' => Pages\ViewCoupon::route('/{record}'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     * @param  array<int, int|string>  $courseIds
     * @param  array<int, int|string>  $bundleIds
     */
    public static function syncTargets(Coupon $coupon, array $categoryIds, array $courseIds, array $bundleIds): void
    {
        $coupon->targets()->delete();

        foreach ($categoryIds as $id) {
            $coupon->targets()->create(['target_type' => CouponTargetType::Category->value, 'target_id' => $id]);
        }

        foreach ($courseIds as $id) {
            $coupon->targets()->create(['target_type' => CouponTargetType::Course->value, 'target_id' => $id]);
        }

        foreach ($bundleIds as $id) {
            $coupon->targets()->create(['target_type' => CouponTargetType::Bundle->value, 'target_id' => $id]);
        }
    }
}
