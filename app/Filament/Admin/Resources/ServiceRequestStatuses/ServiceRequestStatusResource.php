<?php

namespace App\Filament\Admin\Resources\ServiceRequestStatuses;

use App\Filament\Admin\Resources\ServiceRequestStatuses\Schemas\ServiceRequestStatusForm;
use App\Filament\Admin\Resources\ServiceRequestStatuses\Tables\ServiceRequestStatusesTable;
use App\Models\ServiceRequestStatus;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ServiceRequestStatusResource extends Resource
{
    protected static ?string $model = ServiceRequestStatus::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-swatch';

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.service_request_statuses');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.service_request_status');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.service_request_statuses');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_leads_requests_group');
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceRequestStatusForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestStatusesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequestStatuses::route('/'),
            'create' => Pages\CreateServiceRequestStatus::route('/create'),
            'view' => Pages\ViewServiceRequestStatus::route('/{record}'),
            'edit' => Pages\EditServiceRequestStatus::route('/{record}/edit'),
        ];
    }
}
