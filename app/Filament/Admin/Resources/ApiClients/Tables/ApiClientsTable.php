<?php

namespace App\Filament\Admin\Resources\ApiClients\Tables;

use App\Filament\Admin\Resources\ApiClients\ApiClientResource;
use App\Models\ApiClient;
use App\Services\Api\ApiKeyService;
use Filament\Actions\Action;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ApiClientsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('admin.api_clients.fields.name'))->searchable()->sortable(),
            TextColumn::make('key_prefix')
                ->label(__('admin.api_clients.fields.key_prefix'))
                ->formatStateUsing(fn (string $state): string => $state.'…')
                ->fontFamily('mono')
                ->copyable()
                ->copyMessage(__('admin.api_clients.notifications.copied'))
                ->tooltip(__('admin.api_clients.helpers.key_prefix')),
            TextColumn::make('abilities')
                ->label(__('admin.api_clients.fields.abilities'))
                ->formatStateUsing(fn (string $state): string => __('admin.api_clients.abilities.'.$state))
                ->badge(),
            TextColumn::make('status')
                ->label(__('admin.api_clients.fields.status'))
                ->state(fn (ApiClient $record): string => self::status($record))
                ->formatStateUsing(fn (string $state): string => __('admin.api_clients.status.'.$state))
                ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger')
                ->badge(),
            TextColumn::make('last_used_at')
                ->label(__('admin.api_clients.fields.last_used_at'))
                ->dateTime('Y-m-d H:i')
                ->description(fn (ApiClient $record): ?string => $record->last_used_ip)
                ->placeholder('—')
                ->sortable(),
            TextColumn::make('expires_at')
                ->label(__('admin.api_clients.fields.expires_at'))
                ->dateTime('Y-m-d H:i')
                ->placeholder('—')
                ->sortable()
                ->toggleable(),
            TextColumn::make('created_at')->label(__('admin.labels.created_at'))->dateTime('Y-m-d H:i')->sortable()->toggleable(),
        ])->actions([
            EditAction::make()->label(__('admin.actions.edit')),
            Action::make('rotate_key')
                ->label(__('admin.api_clients.actions.rotate'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('admin.api_clients.modals.rotate_heading'))
                ->modalDescription(__('admin.api_clients.modals.rotate_description'))
                ->visible(fn (ApiClient $record): bool => ! $record->isRevoked() && (auth('admin')->user()?->can('update', $record) ?? false))
                ->action(function (ApiClient $record, HasActions $livewire): void {
                    $plainKey = app(ApiKeyService::class)->rotate($record);

                    Notification::make()->title(__('admin.api_clients.notifications.rotated'))->success()->send();

                    $livewire->mountAction('showApiKey', ['key' => $plainKey]);
                })
                ->registerModalActions([ApiClientResource::showKeyAction()]),
            Action::make('revoke')
                ->label(__('admin.api_clients.actions.revoke'))
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('admin.api_clients.modals.revoke_heading'))
                ->modalDescription(__('admin.api_clients.modals.revoke_description'))
                ->visible(fn (ApiClient $record): bool => ! $record->isRevoked() && (auth('admin')->user()?->can('update', $record) ?? false))
                ->action(function (ApiClient $record): void {
                    app(ApiKeyService::class)->revoke($record);

                    Notification::make()->title(__('admin.api_clients.notifications.revoked'))->success()->send();
                }),
        ])->headerActions([
            CreateAction::make()->label(__('admin.actions.create')),
        ])->groupedBulkActions([
            DeleteBulkAction::make()->label(__('admin.actions.delete_bulk')),
        ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading(__('admin.api_clients.empty_state.heading'))
            ->emptyStateDescription(__('admin.api_clients.empty_state.description'));
    }

    private static function status(ApiClient $record): string
    {
        return match (true) {
            $record->isRevoked() => 'revoked',
            $record->isExpired() => 'expired',
            default => 'active',
        };
    }
}
