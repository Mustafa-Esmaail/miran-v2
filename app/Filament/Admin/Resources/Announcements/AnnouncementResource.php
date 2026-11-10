<?php

namespace App\Filament\Admin\Resources\Announcements;

use App\Filament\Admin\Resources\Announcements\Schemas\AnnouncementForm;
use App\Filament\Admin\Resources\Announcements\Tables\AnnouncementsTable;
use App\Models\Announcement;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 11;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.announcements');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.announcement');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.announcements');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.cms');
    }

    public static function form(Schema $schema): Schema
    {
        return AnnouncementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AnnouncementsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'view' => Pages\ViewAnnouncement::route('/{record}'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
