<?php

namespace App\Filament\Admin\Resources\BroadcastMails;

use App\Filament\Admin\Resources\BroadcastMails\RelationManagers\RecipientsRelationManager;
use App\Filament\Admin\Resources\BroadcastMails\Schemas\BroadcastMailForm;
use App\Filament\Admin\Resources\BroadcastMails\Tables\BroadcastMailsTable;
use App\Models\BroadcastMail;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BroadcastMailResource extends Resource
{
    protected static ?string $model = BroadcastMail::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.send_mail');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.broadcast_mail');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.broadcast_mails');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.users_access');
    }

    public static function form(Schema $schema): Schema
    {
        return BroadcastMailForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BroadcastMailsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RecipientsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBroadcastMails::route('/'),
            'create' => Pages\CreateBroadcastMail::route('/create'),
            'view' => Pages\ViewBroadcastMail::route('/{record}'),
        ];
    }
}
