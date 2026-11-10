<?php

namespace App\Filament\Admin\Resources\WorkflowRules;

use App\Filament\Admin\Resources\WorkflowRules\Pages\CreateWorkflowRule;
use App\Filament\Admin\Resources\WorkflowRules\Pages\EditWorkflowRule;
use App\Filament\Admin\Resources\WorkflowRules\Pages\ListWorkflowRules;
use App\Filament\Admin\Resources\WorkflowRules\Pages\ViewWorkflowRule;
use App\Filament\Admin\Resources\WorkflowRules\Schemas\WorkflowRuleForm;
use App\Filament\Admin\Resources\WorkflowRules\Tables\WorkflowRulesTable;
use App\Models\WorkflowRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WorkflowRuleResource extends Resource
{
    protected static ?string $model = WorkflowRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBolt;

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.workflow_rules');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.workflow_rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.workflow_rules');
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
        return WorkflowRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkflowRulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflowRules::route('/'),
            'create' => CreateWorkflowRule::route('/create'),
            'view' => ViewWorkflowRule::route('/{record}'),
            'edit' => EditWorkflowRule::route('/{record}/edit'),
        ];
    }
}
