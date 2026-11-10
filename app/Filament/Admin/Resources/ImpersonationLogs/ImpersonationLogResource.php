<?php

namespace App\Filament\Admin\Resources\ImpersonationLogs;

use App\Filament\Admin\Resources\ImpersonationLogs\Pages\ListImpersonationLogs;
use App\Filament\Admin\Resources\ImpersonationLogs\Pages\ViewImpersonationLog;
use App\Filament\Admin\Resources\ImpersonationLogs\Tables\ImpersonationLogsTable;
use App\Models\ImpersonationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ImpersonationLogResource extends Resource
{
    protected static ?string $model = ImpersonationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFingerPrint;

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.impersonation_logs');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.impersonation_log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.impersonation_logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.settings');
    }

    public static function canViewAny(): bool
    {
        return auth('admin')->user()?->can('impersonation_logs.viewAny') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ImpersonationLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImpersonationLogs::route('/'),
            'view' => ViewImpersonationLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['admin', 'student']);
    }
}
