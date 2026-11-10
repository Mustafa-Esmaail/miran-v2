<?php

namespace App\Filament\Widgets;

use App\Filament\Admin\Resources\ChatRooms\ChatRoomResource;
use App\Models\User;
use App\Notifications\NewChatMessageNotification;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

class AdminChatQuickAccessWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 12,
    ];

    public function getHeading(): ?string
    {
        return __('admin.navigation.chat_rooms');
    }

    public static function canView(): bool
    {
        $adminUser = auth('admin')->user();

        return $adminUser?->can('chat_rooms.viewAny') ?? false;
    }

    public function table(Table $table): Table
    {
        $adminUser = auth('admin')->user();

        return $table
            ->query($this->getUnreadChatNotificationsQuery($adminUser))
            ->columns([
                TextColumn::make('sender_name')
                    ->label(__('admin.labels.author'))
                    ->state(fn (DatabaseNotification $record): string => (string) (data_get($record->data, 'sender_name') ?: __('admin.labels.unknown')))
                    ->searchable(false)
                    ->wrap(),
                TextColumn::make('body')
                    ->label(__('admin.labels.message'))
                    ->state(fn (DatabaseNotification $record): string => (string) (data_get($record->data, 'body') ?: '-'))
                    ->limit(80)
                    ->wrap(),
                TextColumn::make('chat_room_id')
                    ->label(__('admin.labels.chat_room'))
                    ->state(fn (DatabaseNotification $record): string => (string) (data_get($record->data, 'chat_room_id') ?: '-'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('created_at')
                    ->label(__('admin.labels.created_at'))
                    ->since(),
            ])
            ->actions([
                Action::make('view_chat_room')
                    ->label(__('admin.actions.view'))
                    ->icon('heroicon-o-eye')
                    ->url(fn (DatabaseNotification $record): ?string => $this->getChatRoomUrlFromNotification($record)),
                Action::make('mark_as_read')
                    ->label(__('admin.actions.mark_as_read'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (DatabaseNotification $record): bool => $record->read_at === null)
                    ->action(fn (DatabaseNotification $record): bool => $record->markAsRead()),
            ])
            ->headerActions([
                Action::make('view_all')
                    ->label(__('admin.actions.view_all'))
                    ->icon('heroicon-o-list-bullet')
                    ->url($this->getChatRoomsUrl()),
                Action::make('mark_all_as_read')
                    ->label(__('admin.actions.mark_all_as_read'))
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => $this->getUnreadChatCount() > 0)
                    ->action(function (): void {
                        $adminUser = auth('admin')->user();

                        if (! $adminUser) {
                            return;
                        }

                        $adminUser->unreadNotifications()
                            ->where('type', NewChatMessageNotification::class)
                            ->update(['read_at' => now()]);
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading(__('admin.empty_states.no_messages'));
    }

    public function getUnreadChatCount(): int
    {
        return $this->getUnreadChatNotificationsQuery(auth('admin')->user())->count();
    }

    public function getChatRoomsUrl(): ?string
    {
        $adminUser = auth('admin')->user();

        if (! $adminUser?->can('chat_rooms.viewAny')) {
            return null;
        }

        return ChatRoomResource::getUrl('index');
    }

    private function getUnreadChatNotificationsQuery(?User $adminUser): Builder
    {
        if (! $adminUser) {
            return DatabaseNotification::query()->whereRaw('1 = 0');
        }

        return DatabaseNotification::query()
            ->where('notifiable_type', $adminUser::class)
            ->where('notifiable_id', $adminUser->id)
            ->where('type', NewChatMessageNotification::class)
            ->whereNull('read_at')
            ->latest()
            ->limit(5);
    }

    private function getChatRoomUrlFromNotification(DatabaseNotification $notification): ?string
    {
        if (! auth('admin')->user()?->can('chat_rooms.viewAny')) {
            return null;
        }

        $chatRoomId = data_get($notification->data, 'chat_room_id');

        if (! $chatRoomId) {
            return null;
        }

        return ChatRoomResource::getUrl('index').'?room='.$chatRoomId;
    }
}
