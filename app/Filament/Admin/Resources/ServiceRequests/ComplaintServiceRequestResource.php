<?php

namespace App\Filament\Admin\Resources\ServiceRequests;

use App\Enums\ServiceRequestSource;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\ScopesServiceRequestsToEmployee;
use App\Filament\Admin\Resources\ServiceRequests\Pages\CreateComplaintServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\EditComplaintServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\KanbanComplaintServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListComplaintServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewComplaintServiceRequest;
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

class ComplaintServiceRequestResource extends Resource
{
    use ScopesServiceRequestsToEmployee;

    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.complaint_service_requests');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.complaint_service_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.complaint_service_requests');
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
            && ($user->can('complaint_service_requests.viewAny') || $user->can('service_requests.viewAny'));
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceRequestForm::configure($schema, [ServiceRequestSource::Complaint], showAttachments: true);
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
        $query = parent::getEloquentQuery()
            ->where('source', ServiceRequestSource::Complaint)
            ->with(['user', 'course', 'courseGroup', 'bundle', 'assignedTo']);

        return static::scopeToAssignedEmployee($query);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComplaintServiceRequests::route('/'),
            'kanban' => KanbanComplaintServiceRequests::route('/kanban'),
            'create' => CreateComplaintServiceRequest::route('/create'),
            'view' => ViewComplaintServiceRequest::route('/{record}'),
            'edit' => EditComplaintServiceRequest::route('/{record}/edit'),
        ];
    }
}
