<?php

namespace App\Filament\Admin\Resources\ServiceRequests;

use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequestActivities;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewServiceRequestActivity;
use App\Filament\Admin\Resources\ServiceRequests\Tables\ServiceRequestActivitiesTable;
use App\Models\ServiceRequestActivity;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceRequestActivityResource extends Resource
{
    protected static ?string $model = ServiceRequestActivity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.service_request_activities');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.service_request_activity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.service_request_activities');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_leads_requests_group');
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceRequestActivities::route('/'),
            'view' => ViewServiceRequestActivity::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['serviceRequest', 'actor']);
    }
}
