<?php

namespace App\Filament\Admin\Resources\ShareLinks;

use App\Enums\EmployeeRole;
use App\Filament\Admin\Resources\ShareLinks\Schemas\ShareLinkForm;
use App\Filament\Admin\Resources\ShareLinks\Tables\ShareLinksTable;
use App\Models\ShareLink;
use App\Support\CrmScope;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShareLinkResource extends Resource
{
    protected static ?string $model = ShareLink::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.share_links');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.share_link');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.share_links');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_automation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ShareLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShareLinksTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['employee', 'createdBy']);

        if (! static::isRestrictedToSomeEmployees()) {
            return $query;
        }

        return $query->whereIn('employee_id', static::employeeScope()->select('employees.id'));
    }

    /**
     * Employees whose share links the signed-in user may create and see:
     * the CRM role scope, further limited to themselves when they lack
     * the `employees.viewAny` permission.
     */
    public static function employeeScope(): Builder
    {
        $admin = auth('admin')->user();

        return CrmScope::visibleEmployees()
            ->when(! $admin?->can('employees.viewAny'), fn (Builder $query): Builder => $query->where('user_id', $admin?->id));
    }

    protected static function isRestrictedToSomeEmployees(): bool
    {
        $role = CrmScope::viewer()?->role;

        return ($role !== null && $role !== EmployeeRole::GeneralManager)
            || ! auth('admin')->user()?->can('employees.viewAny');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShareLinks::route('/'),
            'create' => Pages\CreateShareLink::route('/create'),
            'view' => Pages\ViewShareLink::route('/{record}'),
        ];
    }
}
