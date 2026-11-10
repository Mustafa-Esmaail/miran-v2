<?php

namespace App\Filament\Admin\Resources\CallQualityCriteria;

use App\Filament\Admin\Resources\CallQualityCriteria\Pages\CreateCallQualityCriterion;
use App\Filament\Admin\Resources\CallQualityCriteria\Pages\EditCallQualityCriterion;
use App\Filament\Admin\Resources\CallQualityCriteria\Pages\ListCallQualityCriteria;
use App\Filament\Admin\Resources\CallQualityCriteria\Schemas\CallQualityCriterionForm;
use App\Filament\Admin\Resources\CallQualityCriteria\Tables\CallQualityCriteriaTable;
use App\Models\CallQualityCriterion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CallQualityCriterionResource extends Resource
{
    protected static ?string $model = CallQualityCriterion::class;

    protected static ?string $slug = 'call-quality-criteria';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?int $navigationSort = 90;

    public static function getNavigationLabel(): string
    {
        return __('admin.call_quality.criteria.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('admin.call_quality.criteria.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.call_quality.criteria.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.crm');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.crm_quality_group');
    }

    public static function getRecordTitle(?Model $record): string
    {
        return $record instanceof CallQualityCriterion ? (string) $record->name : static::getModelLabel();
    }

    /**
     * The policy has no "reorder" ability; reordering is part of managing criteria.
     */
    public static function canReorder(): bool
    {
        return auth('admin')->user()?->can('call_quality_criteria.manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return CallQualityCriterionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CallQualityCriteriaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCallQualityCriteria::route('/'),
            'create' => CreateCallQualityCriterion::route('/create'),
            'edit' => EditCallQualityCriterion::route('/{record}/edit'),
        ];
    }
}
