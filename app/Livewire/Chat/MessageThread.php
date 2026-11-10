<?php

namespace App\Livewire\Chat;

use App\Models\ChatRoom;
use App\Services\ChatService;
use Illuminate\View\View;
use Livewire\Component;

class MessageThread extends Component
{
    public ?int $roomId = null;

    public string $body = '';

    public ?int $replyToMessageId = null;

    public function mount(?int $roomId = null): void
    {
        $this->roomId = $roomId;

        if ($this->roomId) {
            $this->markRead();
        }
    }

    public function updatedRoomId(): void
    {
        $this->body = '';
        $this->replyToMessageId = null;

        if ($this->roomId) {
            $this->markRead();
        }
    }

    public function send(): void
    {
        $this->validate(['body' => 'required|string|max:5000']);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $room = $this->resolveRoom();

        app(ChatService::class)->sendMessage($user, $room, $this->body, $this->replyToMessageId);

        $this->body = '';
        $this->replyToMessageId = null;
        $this->markRead();
        $this->dispatch('thread-scroll-bottom');
    }

    public function setReply(int $messageId): void
    {
        $this->replyToMessageId = $messageId;
    }

    public function clearReply(): void
    {
        $this->replyToMessageId = null;
    }

    public function pollMessages(): void
    {
        if ($this->roomId) {
            $this->markRead();
        }
        $this->dispatch('thread-messages-polled');
    }

    public function render(): View
    {
        $room = $this->resolveRoom();
        $messages = null;

        if ($room) {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            $messages = app(ChatService::class)->getMessages($user, $room);
        }

        return view('livewire.chat.message-thread', [
            'room' => $room,
            'messages' => $messages,
        ]);
    }

    private function resolveRoom(): ?ChatRoom
    {
        if (! $this->roomId) {
            return null;
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $rooms = app(ChatService::class)->getRoomsForUser($user);
        $room = $rooms->firstWhere('id', $this->roomId);

        abort_unless($room !== null, 403);

        return $room;
    }

    private function markRead(): void
    {
        if (! $this->roomId) {
            return;
        }

        $room = ChatRoom::query()->find($this->roomId);
        if (! $room) {
            return;
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();
        app(ChatService::class)->markRoomRead($user, $room);
    }
}
