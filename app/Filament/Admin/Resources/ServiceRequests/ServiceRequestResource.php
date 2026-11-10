<?php

namespace App\Filament\Admin\Resources\ServiceRequests;

use App\Filament\Admin\Resources\ServiceRequests\Concerns\ScopesServiceRequestsToEmployee;
use App\Filament\Admin\Resources\ServiceRequests\Pages\CreateServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\EditServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\KanbanServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\RelationManagers\ActivitiesRelationManager;
use App\Filament\Admin\Resources\ServiceRequests\RelationManagers\CrmActivitiesRelationManager;
use App\Filament\Admin\Resources\ServiceRequests\RelationManagers\CrmTasksRelationManager;
use App\Filament\Admin\Resources\ServiceRequests\Schemas\ServiceRequestForm;
use App\Filament\Admin\Resources\ServiceRequests\Schemas\ServiceRequestInfolist;
use App\Filament\Admin\Resources\ServiceRequests\Tables\ServiceRequestsTable;
use App\Models\ServiceRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServiceRequestResource extends Resource
{
    use ScopesServiceRequestsToEmployee;

    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.service_requests');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.service_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.service_requests');
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
        return ServiceRequestForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServiceRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
            CrmActivitiesRelationManager::class,
            CrmTasksRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return static::scopeToAssignedEmployee(
            parent::getEloquentQuery()->with(['user', 'course', 'courseGroup', 'bundle', 'assignedTo'])
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceRequests::route('/'),
            'kanban' => KanbanServiceRequests::route('/kanban'),
            'create' => CreateServiceRequest::route('/create'),
            'view' => ViewServiceRequest::route('/{record}'),
            'edit' => EditServiceRequest::route('/{record}/edit'),
        ];
    }
}
