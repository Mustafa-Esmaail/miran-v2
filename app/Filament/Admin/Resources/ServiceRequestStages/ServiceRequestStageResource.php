<?php

namespace App\Filament\Admin\Resources\ServiceRequestStages;

use App\Filament\Admin\Resources\ServiceRequestStages\Schemas\ServiceRequestStageForm;
use App\Filament\Admin\Resources\ServiceRequestStages\Tables\ServiceRequestStagesTable;
use App\Models\ServiceRequestStage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ServiceRequestStageResource extends Resource
{
    protected static ?string $model = ServiceRequestStage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 9;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.service_request_stages');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.service_request_stage');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.service_request_stages');
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
        return ServiceRequestStageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceRequestStagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServiceRequestStages::route('/'),
            'create' => Pages\CreateServiceRequestStage::route('/create'),
            'view' => Pages\ViewServiceRequestStage::route('/{record}'),
            'edit' => Pages\EditServiceRequestStage::route('/{record}/edit'),
        ];
    }
}
