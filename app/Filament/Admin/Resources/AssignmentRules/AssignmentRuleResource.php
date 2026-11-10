<?php

namespace App\Filament\Admin\Resources\AssignmentRules;

use App\Filament\Admin\Resources\AssignmentRules\Pages\CreateAssignmentRule;
use App\Filament\Admin\Resources\AssignmentRules\Pages\EditAssignmentRule;
use App\Filament\Admin\Resources\AssignmentRules\Pages\ListAssignmentRules;
use App\Filament\Admin\Resources\AssignmentRules\Pages\ViewAssignmentRule;
use App\Filament\Admin\Resources\AssignmentRules\Schemas\AssignmentRuleForm;
use App\Filament\Admin\Resources\AssignmentRules\Tables\AssignmentRulesTable;
use App\Models\AssignmentRule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssignmentRuleResource extends Resource
{
    protected static ?string $model = AssignmentRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.assignment_rules');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.assignment_rule');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.assignment_rules');
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
        return AssignmentRuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssignmentRulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssignmentRules::route('/'),
            'create' => CreateAssignmentRule::route('/create'),
            'view' => ViewAssignmentRule::route('/{record}'),
            'edit' => EditAssignmentRule::route('/{record}/edit'),
        ];
    }
}
