<?php

namespace App\Filament\Admin\Resources\ServiceRequests;

use App\Enums\ServiceRequestSource;
use App\Filament\Admin\Resources\ServiceRequests\Concerns\ScopesServiceRequestsToEmployee;
use App\Filament\Admin\Resources\ServiceRequests\Pages\CreateB2cServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\EditB2cServiceRequest;
use App\Filament\Admin\Resources\ServiceRequests\Pages\KanbanB2cServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ListB2cServiceRequests;
use App\Filament\Admin\Resources\ServiceRequests\Pages\ViewB2cServiceRequest;
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

class B2cServiceRequestResource extends Resource
{
    use ScopesServiceRequestsToEmployee;

    /**
     * Every source that is not carved out into its own resource
     * (see ComplaintServiceRequestResource, B2bServiceRequestResource).
     *
     * @var array<int, ServiceRequestSource>
     */
    public const EXCLUDED_SOURCES = [
        ServiceRequestSource::Complaint,
        ServiceRequestSource::CorporateTraining,
    ];

    protected static ?string $model = ServiceRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.b2c_service_requests');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.b2c_service_request');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.b2c_service_requests');
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
            && ($user->can('b2c_service_requests.viewAny') || $user->can('service_requests.viewAny'));
    }

    public static function form(Schema $schema): Schema
    {
        $allowedSources = array_values(array_filter(
            ServiceRequestSource::cases(),
            fn (ServiceRequestSource $source): bool => ! in_array($source, self::EXCLUDED_SOURCES, true)
        ));

        return ServiceRequestForm::configure($schema, $allowedSources);
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
            ->whereNotIn('source', self::EXCLUDED_SOURCES)
            ->with(['user', 'course', 'courseGroup', 'bundle', 'assignedTo']);

        return static::scopeToAssignedEmployee($query);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListB2cServiceRequests::route('/'),
            'kanban' => KanbanB2cServiceRequests::route('/kanban'),
            'create' => CreateB2cServiceRequest::route('/create'),
            'view' => ViewB2cServiceRequest::route('/{record}'),
            'edit' => EditB2cServiceRequest::route('/{record}/edit'),
        ];
    }
}
