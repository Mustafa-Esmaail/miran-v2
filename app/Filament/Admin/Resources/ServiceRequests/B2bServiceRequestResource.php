<?php

namespace App\Filament\Admin\Resources\ServiceRequests;

use App\Enums\ServiceRequestSource;
use App\Filament\Admin\Resources\Deals\RelationManagers\DealsRelationManager;
use App\Filament\Admin\Resources\Deals\RelationManagers\QuotesRelationManager;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\ScopesServiceRequestsToEmployee;
use App\Filament\Admin\Resources\ServiceRequests\Pages\CreateB2bServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\EditB2bServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\KanbanB2bServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListB2bServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewB2bServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\RelationManagers\ActivitiesRelationManager;
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

class B2bServiceRequestResource extends Resource
{
    use ScopesServiceRequestsToEmployee;

    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.b2b_service_requests');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.b2b_service_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.b2b_service_requests');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_leads_requests_group');
    }

    public static function canViewAny(): bool
    {
        $user = auth('admin')->user();

        return $user !== null
            && ($user->can('b2b_service_requests.viewAny') || $user->can('service_requests.viewAny'));
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceRequestForm::configure($schema, [ServiceRequestSource::CorporateTraining], showCompanyFields: true);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServiceRequestInfolist::configure($schema, showCompanyFields: true);
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestsTable::configure($table, showCompanyFields: true);
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
            CrmTasksRelationManager::class,
            DealsRelationManager::class,
            QuotesRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->where('source', ServiceRequestSource::CorporateTraining)
            ->with(['user', 'course', 'courseGroup', 'bundle', 'assignedTo']);

        return static::scopeToAssignedEmployee($query);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListB2bServiceRequests::route('/'),
            'kanban' => KanbanB2bServiceRequests::route('/kanban'),
            'create' => CreateB2bServiceRequest::route('/create'),
            'view' => ViewB2bServiceRequest::route('/{record}'),
            'edit' => EditB2bServiceRequest::route('/{record}/edit'),
        ];
    }
}
