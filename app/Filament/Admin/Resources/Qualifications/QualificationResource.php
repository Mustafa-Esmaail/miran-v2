<?php

namespace App\Filament\Admin\Resources\Qualifications;

use App\Filament\Admin\Resources\Qualifications\Schemas\QualificationForm;
use App\Filament\Admin\Resources\Qualifications\Tables\QualificationsTable;
use App\Models\Qualification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class QualificationResource extends Resource
{
    protected static ?string $model = Qualification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 11;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.qualifications');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.qualification');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.qualifications');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return QualificationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QualificationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQualifications::route('/'),
            'create' => Pages\CreateQualification::route('/create'),
            'view' => Pages\ViewQualification::route('/{record}'),
            'edit' => Pages\EditQualification::route('/{record}/edit'),
        ];
    }
}
