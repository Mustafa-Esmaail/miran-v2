<?php

namespace App\Livewire\Chat;

use App\Models\ChatRoom;
use App\Services\ChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\View\View;
use Livewire\Component;

class FloatingChat extends Component
{
    public bool $isOpen = false;

    public ?int $activeRoomId = null;

    public string $body = '';

    public ?int $replyToMessageId = null;

    public function toggle(): void
    {
        if ($this->isOpen) {
            $this->closeChat();

            return;
        }

        if (! auth()->check()) {
            return;
        }

        $this->isOpen = true;
        $this->showRooms();
    }

    public function closeChat(): void
    {
        $this->isOpen = false;
        $this->activeRoomId = null;
        $this->body = '';
        $this->replyToMessageId = null;
    }

    public function openRoom(int $roomId): void
    {
        $this->ensureAccess($roomId);
        $this->activeRoomId = $roomId;
        $this->body = '';
        $this->replyToMessageId = null;
        $this->markRead();
        $this->dispatch('fc-scroll-bottom');
    }

    public function showRooms(): void
    {
        $this->activeRoomId = null;
        $this->body = '';
        $this->replyToMessageId = null;
    }

    public function send(): void
    {
        $this->validate(['body' => 'required|string|max:5000']);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $room = $this->resolveActiveRoom();

        try {
            app(ChatService::class)->sendMessage($user, $room, $this->body, $this->replyToMessageId);
        } catch (AuthorizationException $e) {
            $this->addError('body', $e->getMessage());

            return;
        }

        $this->body = '';
        $this->replyToMessageId = null;
        $this->markRead();
        $this->dispatch('fc-scroll-bottom');
    }

    public function setReply(int $messageId): void
    {
        $this->replyToMessageId = $messageId;
    }

    public function clearReply(): void
    {
        $this->replyToMessageId = null;
    }

    public function pollChat(): void
    {
        if ($this->isOpen && $this->activeRoomId) {
            $this->markRead();
        }
        $this->dispatch('fc-messages-polled');
    }

    public function render(): View
    {
        if (! auth()->check()) {
            return view('livewire.chat.floating-chat', ['rooms' => collect(), 'messages' => null, 'activeRoom' => null, 'unreadCount' => 0]);
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $service = app(ChatService::class);

        // Always fetch unread count so the badge on the closed button stays current.
        $unreadCount = $service->getUnreadCountForUser($user);

        $rooms = collect();
        $activeRoom = null;
        $messages = null;

        if ($this->isOpen) {
            $rooms = $service->getRoomsForUser($user);
            $activeRoom = $this->activeRoomId ? $rooms->firstWhere('id', $this->activeRoomId) : null;

            if ($activeRoom) {
                $messages = $service->getLatestMessages($user, $activeRoom);
            }
        }

        $isChatBanned = auth()->user()?->isChatBanned() ?? false;

        return view('livewire.chat.floating-chat', compact('rooms', 'unreadCount', 'activeRoom', 'messages', 'isChatBanned'));
    }

    private function resolveActiveRoom(): ChatRoom
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $rooms = app(ChatService::class)->getRoomsForUser($user);
        $room = $rooms->firstWhere('id', $this->activeRoomId);

        abort_unless($room !== null, 403);

        return $room;
    }

    private function ensureAccess(int $roomId): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $rooms = app(ChatService::class)->getRoomsForUser($user);

        abort_unless($rooms->contains('id', $roomId), 403);
    }

    private function markRead(): void
    {
        if (! $this->activeRoomId) {
            return;
        }

        $room = ChatRoom::query()->find($this->activeRoomId);
        if ($room) {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            app(ChatService::class)->markRoomRead($user, $room);
        }
    }
}
