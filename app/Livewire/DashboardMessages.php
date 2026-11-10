<?php

namespace App\Livewire;

use App\Services\ChatService;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class DashboardMessages extends Component
{
    public ?int $activeRoomId = null;

    public bool $mobileThreadVisible = false;

    public function mount(?int $room = null): void
    {
        $this->activeRoomId = $room;
        $this->mobileThreadVisible = $room !== null;
    }

    #[On('room-selected')]
    public function selectRoom(int $roomId): void
    {
        $this->ensureAccess($roomId);
        $this->activeRoomId = $roomId;
        $this->mobileThreadVisible = true;
    }

    public function hideMobileThread(): void
    {
        $this->mobileThreadVisible = false;
    }

    public function render(): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $rooms = app(ChatService::class)->getRoomsForUser($user);
        $activeRoom = $this->activeRoomId ? $rooms->firstWhere('id', $this->activeRoomId) : null;

        if (! $activeRoom && $rooms->isNotEmpty()) {
            $activeRoom = $rooms->first();
            $this->activeRoomId = $activeRoom->id;
        }

        return view('livewire.dashboard-messages', [
            'rooms' => $rooms,
            'activeRoom' => $activeRoom,
        ])->layout('layouts.app');
    }

    private function ensureAccess(int $roomId): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $rooms = app(ChatService::class)->getRoomsForUser($user);

        abort_unless($rooms->contains('id', $roomId), 403);
    }
}
