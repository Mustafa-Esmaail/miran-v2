<?php

namespace App\Filament\Admin\Resources\ChatRooms;

use App\Filament\Admin\Resources\ChatRooms\Tables\ChatRoomsTable;
use App\Models\ChatRoom;
use App\Notifications\NewChatMessageNotification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class ChatRoomResource extends Resource
{
    protected static ?string $model = ChatRoom::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('admin.navigation.chat_rooms');
    }

    public static function getNavigationBadge(): ?string
    {
        $admin = auth('admin')->user();
        if (! $admin) {
            return null;
        }

        $count = DatabaseNotification::query()
            ->where('notifiable_type', $admin::class)
            ->where('notifiable_id', $admin->id)
            ->where('type', NewChatMessageNotification::class)
            ->whereNull('read_at')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getModelLabel(): string
    {
        return __('admin.models.chat_room');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.chat_rooms');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav_groups.lms_management');
    }

    public static function getNavigationParentItem(): ?string
    {
        return __('admin.navigation.lms_engagement_group');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['participants.user', 'course', 'courseGroup']);

        $user = auth('admin')->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('instructor')) {
            return $query->whereHas('participants', function (Builder $participantQuery) use ($user): void {
                $participantQuery
                    ->where('user_id', $user->id)
                    ->whereNull('left_at');
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return ChatRoomsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatRooms::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** Prevent Filament from showing an Edit page/button. */
    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
