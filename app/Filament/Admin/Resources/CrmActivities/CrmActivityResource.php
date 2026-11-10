<?php

namespace App\Filament\Admin\Resources\CrmActivities;

use App\Filament\Admin\Resources\CrmActivities\Pages\CreateCrmActivity;
use App\Filament\Admin\Resources\CrmActivities\Pages\EditCrmActivity;
use App\Filament\Admin\Resources\CrmActivities\Pages\ListCrmActivities;
use App\Filament\Admin\Resources\CrmActivities\Pages\ViewCrmActivity;
use App\Filament\Admin\Resources\CrmActivities\Schemas\CrmActivityForm;
use App\Filament\Admin\Resources\CrmActivities\Tables\CrmActivitiesTable;
use App\Models\CrmActivity;
use App\Traits\CrmRoleScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CrmActivityResource extends Resource
{
    use CrmRoleScope;

    protected static ?string $model = CrmActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhone;

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.crm_activities');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.crm_activity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.crm_activities');
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
        return CrmActivityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrmActivitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmActivities::route('/'),
            'create' => CreateCrmActivity::route('/create'),
            'view' => ViewCrmActivity::route('/{record}'),
            'edit' => EditCrmActivity::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('employee');
        $employee = (new self)->currentEmployee();

        return (new self)->scopeActivities($query, $employee);
    }
}
