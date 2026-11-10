<?php

namespace App\Livewire\Chat;

use App\Models\ChatRoom;
use App\Services\ChatService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class CourseSidebar extends Component
{
    public int $courseGroupId;

    public ?int $activeRoomId = null;

    public string $body = '';

    public ?int $replyToMessageId = null;

    public function mount(int $courseGroupId): void
    {
        $this->courseGroupId = $courseGroupId;
    }

    public function selectRoom(int $roomId): void
    {
        $room = $this->findRoom($roomId);

        abort_unless($room !== null, 403);

        $this->activeRoomId = $room->id;
        $this->body = '';
        $this->replyToMessageId = null;
        $this->markRead($room);
    }

    public function send(): void
    {
        $this->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $room = $this->resolveActiveRoom();

        /** @var \App\Models\User $user */
        $user = auth()->user();

        app(ChatService::class)->sendMessage($user, $room, $this->body, $this->replyToMessageId);

        $this->body = '';
        $this->replyToMessageId = null;
        $this->markRead($room);
    }

    public function setReply(int $messageId): void
    {
        $this->replyToMessageId = $messageId;
    }

    public function clearReply(): void
    {
        $this->replyToMessageId = null;
    }

    public function pollSidebar(): void
    {
        $activeRoom = $this->resolveActiveRoomFromCollection($this->roomsForSidebar());

        if ($activeRoom) {
            $this->markRead($activeRoom);
        }
    }

    public function render(): View
    {
        $rooms = $this->roomsForSidebar();
        $previousActiveRoomId = $this->activeRoomId;
        $activeRoom = $this->resolveActiveRoomFromCollection($rooms);
        $messages = collect();

        if ($activeRoom) {
            /** @var \App\Models\User $user */
            $user = auth()->user();
            $messages = app(ChatService::class)->getLatestMessages($user, $activeRoom, 12);

            if ($previousActiveRoomId !== $activeRoom->id) {
                $this->markRead($activeRoom);
            }
        }

        return view('livewire.chat.course-sidebar', [
            'rooms' => $rooms,
            'activeRoom' => $activeRoom,
            'messages' => $messages,
        ]);
    }

    /**
     * @return Collection<int, ChatRoom>
     */
    private function roomsForSidebar(): Collection
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return app(ChatService::class)
            ->getRoomsForUser($user)
            ->where('course_group_id', $this->courseGroupId)
            ->values();
    }

    /**
     * @param  Collection<int, ChatRoom>  $rooms
     */
    private function resolveActiveRoomFromCollection(Collection $rooms): ?ChatRoom
    {
        $activeRoom = $this->activeRoomId ? $rooms->firstWhere('id', $this->activeRoomId) : null;

        if (! $activeRoom && $rooms->isNotEmpty()) {
            /** @var ChatRoom $activeRoom */
            $activeRoom = $rooms->first();
            $this->activeRoomId = $activeRoom->id;
        }

        return $activeRoom;
    }

    private function resolveActiveRoom(): ChatRoom
    {
        $room = $this->findRoom($this->activeRoomId);

        abort_unless($room !== null, 403);

        return $room;
    }

    private function findRoom(?int $roomId): ?ChatRoom
    {
        if (! $roomId) {
            return null;
        }

        return $this->roomsForSidebar()->firstWhere('id', $roomId);
    }

    private function markRead(ChatRoom $room): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        app(ChatService::class)->markRoomRead($user, $room);
    }
}
