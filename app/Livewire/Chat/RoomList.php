<?php

namespace App\Livewire\Chat;

use App\Services\ChatService;
use Illuminate\View\View;
use Livewire\Component;

class RoomList extends Component
{
    public ?int $activeRoomId = null;

    public function pollRooms(): void {}

    public function render(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $rooms = app(ChatService::class)->getRoomsForUser($user);
        $unreadCount = app(ChatService::class)->getUnreadCountForUser($user);

        return view('livewire.chat.room-list', [
            'rooms' => $rooms,
            'unreadCount' => $unreadCount,
        ]);
    }
}
