<?php

namespace App\Filament\Admin\Resources\NotificationLogs;

use App\Filament\Admin\Resources\NotificationLogs\Tables\NotificationLogsTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class NotificationLogResource extends Resource
{
    protected static ?string $model = DatabaseNotification::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.notification_logs');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.notification_log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.notification_logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.settings');
    }

    public static function table(Table $table): Table
    {
        return NotificationLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationLogs::route('/'),
            'view' => Pages\ViewNotificationLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->latest();
    }
}
