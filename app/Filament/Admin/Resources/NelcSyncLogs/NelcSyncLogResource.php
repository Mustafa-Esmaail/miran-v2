<?php

namespace App\Filament\Admin\Resources\NelcSyncLogs;

use App\Enums\NelcSyncStatus;
use App\Filament\Admin\Resources\NelcSyncLogs\Pages\ListNelcSyncLogs;
use App\Filament\Admin\Resources\NelcSyncLogs\Pages\ViewNelcSyncLog;
use App\Filament\Admin\Resources\NelcSyncLogs\Tables\NelcSyncLogsTable;
use App\Models\NelcSyncLog;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NelcSyncLogResource extends Resource
{
    protected static ?string $model = NelcSyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.nelc_sync_logs');
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.nelc_sync_log');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.nelc_sync_logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.settings');
    }

    public static function canViewAny(): bool
    {
        return auth('admin')->user()?->can('nelc_sync_logs.viewAny') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.sections.details'))
                ->columns(2)
                ->schema([
                    TextEntry::make('event_type')->label(__('admin.labels.event_type'))->badge(),
                    TextEntry::make('status')
                        ->label(__('admin.labels.status'))
                        ->formatStateUsing(function ($state): string {
                            $value = $state instanceof \BackedEnum ? $state->value : (string) $state;
                            $key = 'admin.options.'.$value;

                            return __($key) !== $key ? __($key) : $value;
                        })
                        ->color(fn ($state): string => match ($state instanceof NelcSyncStatus ? $state : NelcSyncStatus::from((string) $state)) {
                            NelcSyncStatus::Sent => 'success',
                            NelcSyncStatus::Failed => 'danger',
                            NelcSyncStatus::Pending => 'warning',
                        })
                        ->badge(),
                    TextEntry::make('user.name')->label(__('admin.labels.trainee')),
                    TextEntry::make('user.email')->label(__('admin.labels.email')),
                    TextEntry::make('course.slug')->label(__('admin.labels.course')),
                    TextEntry::make('courseGroup.title')
                        ->label(__('admin.labels.group'))
                        ->formatStateUsing(fn ($state, NelcSyncLog $record) => $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-'),
                    TextEntry::make('attempts')->label(__('admin.labels.attempts')),
                    TextEntry::make('synced_at')->label(__('admin.labels.synced_at'))->dateTime('Y-m-d H:i'),
                    TextEntry::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i'),
                ]),
            Section::make(__('admin.labels.response'))
                ->schema([
                    TextEntry::make('response')
                        ->label('')
                        ->formatStateUsing(fn ($state): string => filled($state) ? (string) $state : '-')
                        ->columnSpanFull(),
                ]),
            Section::make(__('admin.labels.payload'))
                ->schema([
                    TextEntry::make('payload')
                        ->label('')
                        ->formatStateUsing(function ($state): string {
                            if (is_string($state)) {
                                $decoded = json_decode($state, true);
                                $state = json_last_error() === JSON_ERROR_NONE ? $decoded : $state;
                            }

                            if (is_array($state)) {
                                return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                            }

                            return filled($state) ? (string) $state : '-';
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return NelcSyncLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNelcSyncLogs::route('/'),
            'view' => ViewNelcSyncLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'course', 'courseGroup']);
    }
}
