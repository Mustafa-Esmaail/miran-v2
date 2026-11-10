<?php

namespace App\Filament\Admin\Resources\ChatRooms\Pages;

use App\Filament\Admin\Resources\ChatRooms\ChatRoomResource;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomParticipant;
use App\Models\User;
use App\Services\ChatService;
use Filament\Resources\Pages\Page;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class ListChatRooms extends Page
{
    protected static string $resource = ChatRoomResource::class;

    protected string $view = 'filament.resources.chat-room-resource.pages.list-chat-rooms';

    public ?int $selectedRoomId = null;

    public string $body = '';

    public ?int $replyToMessageId = null;

    public ?int $latestMessageId = null;

    public function mount(): void
    {
        $requestedRoomId = request()->integer('room');

        if ($requestedRoomId > 0 && $this->canSelectRoom($requestedRoomId)) {
            $this->selectedRoomId = $requestedRoomId;
        } else {
            $this->selectedRoomId = $this->getRooms()->first()?->id;
        }

        if ($this->selectedRoomId) {
            $this->latestMessageId = ChatMessage::query()
                ->where('chat_room_id', $this->selectedRoomId)
                ->max('id');
            $this->markRoomAsRead($this->selectedRoomId);
        }
    }

    public function getBreadcrumb(): ?string
    {
        return __('admin.pages.list');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.list_title', ['resource' => static::getResource()::getTitleCasePluralModelLabel()]);
    }

    public function selectRoom(int $roomId): void
    {
        if (! $this->canSelectRoom($roomId)) {
            return;
        }

        $this->selectedRoomId = $roomId;
        $this->body = '';
        $this->replyToMessageId = null;
        $this->latestMessageId = ChatMessage::query()
            ->where('chat_room_id', $roomId)
            ->max('id');
        $this->markRoomAsRead($roomId);
        $this->dispatch('admin-chat-scroll');
    }

    public function sendMessage(): void
    {
        $room = $this->getSelectedRoom();
        if (! $room) {
            return;
        }

        $this->validate(['body' => 'required|string|max:5000']);

        /** @var \App\Models\User $sender */
        $sender = auth('admin')->user();

        try {
            app(ChatService::class)->adminSendMessage(
                $sender,
                $room,
                $this->body,
                $this->replyToMessageId,
            );
        } catch (AuthorizationException $e) {
            $this->addError('body', $e->getMessage());

            return;
        }

        $this->body = '';
        $this->replyToMessageId = null;
        $this->dispatch('admin-chat-scroll');
    }

    public function hideMessage(int $messageId, string $reason): void
    {
        $room = $this->getSelectedRoom();
        if (! $room) {
            return;
        }

        $reason = trim($reason);
        if ($reason === '') {
            return;
        }

        $message = ChatMessage::find($messageId);

        if (! $message || $message->chat_room_id !== $room->id) {
            return;
        }

        /** @var \App\Models\User $admin */
        $admin = auth('admin')->user();

        app(ChatService::class)->adminHideMessage($admin, $message, $reason);
        $this->dispatch('admin-chat-scroll');
    }

    public function unhideMessage(int $messageId): void
    {
        $room = $this->getSelectedRoom();
        if (! $room) {
            return;
        }

        $message = ChatMessage::find($messageId);

        if (! $message || $message->chat_room_id !== $room->id) {
            return;
        }

        /** @var \App\Models\User $admin */
        $admin = auth('admin')->user();

        app(ChatService::class)->adminUnhideMessage($admin, $message);
        $this->dispatch('admin-chat-scroll');
    }

    public function toggleChatBan(int $userId): void
    {
        /** @var \App\Models\User $admin */
        $admin = auth('admin')->user();

        if (! $admin?->can('users.chat_ban')) {
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            return;
        }

        $user->update(['is_chat_banned' => ! $user->is_chat_banned]);
    }

    public function refreshMessages(): void
    {
        if (! $this->selectedRoomId) {
            return;
        }

        $currentLatestId = ChatMessage::query()
            ->where('chat_room_id', $this->selectedRoomId)
            ->max('id');

        if ($currentLatestId && $currentLatestId !== $this->latestMessageId) {
            $this->latestMessageId = $currentLatestId;
            $this->markRoomAsRead($this->selectedRoomId);
            $this->dispatch('admin-chat-scroll');
        }
    }

    public function setReply(int $messageId): void
    {
        $this->replyToMessageId = $messageId;
    }

    public function clearReply(): void
    {
        $this->replyToMessageId = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getViewData(): array
    {
        $rooms = $this->getRooms();
        $unreadCounts = $this->getUnreadCounts($rooms);

        $rooms = $rooms->sort(function (ChatRoom $a, ChatRoom $b) use ($unreadCounts): int {
            $aHasUnread = ($unreadCounts[$a->id] ?? 0) > 0 ? 1 : 0;
            $bHasUnread = ($unreadCounts[$b->id] ?? 0) > 0 ? 1 : 0;

            if ($aHasUnread !== $bHasUnread) {
                return $bHasUnread - $aHasUnread;
            }

            return ($b->last_message_at?->timestamp ?? 0) <=> ($a->last_message_at?->timestamp ?? 0);
        })->values();

        $selectedRoom = $this->getSelectedRoom();

        $messages = collect();
        $totalMessages = 0;
        $replyToMessage = null;
        $participants = collect();

        if ($selectedRoom) {
            $messages = ChatMessage::query()
                ->where('chat_room_id', $selectedRoom->id)
                ->with(['sender', 'replyTo.sender', 'hiddenBy'])
                ->orderByDesc('created_at')
                ->take(80)
                ->get()
                ->reverse()
                ->values();

            $totalMessages = ChatMessage::query()
                ->where('chat_room_id', $selectedRoom->id)
                ->count();

            $replyToMessage = $this->replyToMessageId
                ? ChatMessage::query()->with('sender')->find($this->replyToMessageId)
                : null;

            $participants = ChatRoomParticipant::query()
                ->where('chat_room_id', $selectedRoom->id)
                ->whereNull('left_at')
                ->with('user')
                ->orderBy('role_in_room')
                ->get();
        }

        return [
            'rooms' => $rooms,
            'unreadCounts' => $unreadCounts,
            'roomPreviews' => $this->getRoomPreviews($rooms),
            'selectedRoom' => $selectedRoom,
            'messages' => $messages,
            'totalMessages' => $totalMessages,
            'replyToMessage' => $replyToMessage,
            'participants' => $participants,
        ];
    }

    /**
     * @return Collection<int, ChatRoom>
     */
    private function getRooms(): Collection
    {
        return ChatRoomResource::getEloquentQuery()
            ->withCount('messages')
            ->with([
                'courseGroup',
                'course',
                'participants' => fn ($q) => $q->whereNull('left_at')->with('user:id,name'),
            ])
            ->orderByDesc('last_message_at')
            ->get();
    }

    private function getSelectedRoom(): ?ChatRoom
    {
        if (! $this->selectedRoomId) {
            return null;
        }

        return ChatRoomResource::getEloquentQuery()
            ->whereKey($this->selectedRoomId)
            ->first();
    }

    private function canSelectRoom(int $roomId): bool
    {
        return ChatRoomResource::getEloquentQuery()
            ->whereKey($roomId)
            ->exists();
    }

    public function markAllRoomsAsRead(): void
    {
        $adminId = auth('admin')->id();

        $maxIds = ChatMessage::query()
            ->selectRaw('chat_room_id, MAX(id) as max_id')
            ->groupBy('chat_room_id')
            ->pluck('max_id', 'chat_room_id');

        foreach ($maxIds as $roomId => $maxId) {
            cache()->put(
                "admin_chat_read_{$adminId}_{$roomId}",
                (int) $maxId,
                now()->addDays(30),
            );
        }
    }

    private function markRoomAsRead(int $roomId): void
    {
        $adminId = auth('admin')->id();
        $maxId = ChatMessage::query()
            ->where('chat_room_id', $roomId)
            ->max('id') ?? 0;

        cache()->put(
            "admin_chat_read_{$adminId}_{$roomId}",
            (int) $maxId,
            now()->addDays(30),
        );
    }

    /**
     * @param  Collection<int, ChatRoom>  $rooms
     * @return array<int, int>
     */
    private function getUnreadCounts(Collection $rooms): array
    {
        if ($rooms->isEmpty()) {
            return [];
        }

        $adminId = auth('admin')->id();
        $result = [];

        foreach ($rooms as $room) {
            $lastReadId = (int) cache()->get("admin_chat_read_{$adminId}_{$room->id}", 0);
            $result[$room->id] = ChatMessage::query()
                ->where('chat_room_id', $room->id)
                ->where('id', '>', $lastReadId)
                ->count();
        }

        return $result;
    }

    /**
     * @param  Collection<int, ChatRoom>  $rooms
     * @return array<int, string>
     */
    private function getRoomPreviews(Collection $rooms): array
    {
        $roomIds = $rooms->pluck('id')->all();

        if ($roomIds === []) {
            return [];
        }

        return ChatMessage::query()
            ->whereIn('chat_room_id', $roomIds)
            ->orderByDesc('id')
            ->get(['chat_room_id', 'body'])
            ->unique('chat_room_id')
            ->mapWithKeys(fn (ChatMessage $message): array => [$message->chat_room_id => $message->body])
            ->all();
    }
}
