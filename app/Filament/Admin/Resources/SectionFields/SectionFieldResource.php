<?php

namespace App\Filament\Admin\Resources\SectionFields;

use App\Filament\Admin\Resources\SectionFields\Schemas\SectionFieldForm;
use App\Filament\Admin\Resources\SectionFields\Tables\SectionFieldsTable;
use App\Models\SectionField;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SectionFieldResource extends Resource
{
    protected static ?string $model = SectionField::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.section_fields');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.section_field');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.section_fields');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.cms');
    }

    public static function form(Schema $schema): Schema
    {
        return SectionFieldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectionFieldsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSectionFields::route('/'),
            'create' => Pages\CreateSectionField::route('/create'),
            'view' => Pages\ViewSectionField::route('/{record}'),
            'edit' => Pages\EditSectionField::route('/{record}/edit'),
        ];
    }
}
