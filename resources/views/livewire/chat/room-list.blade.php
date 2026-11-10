<div class="flex h-full flex-col" data-chat-debug-root="room-list" wire:poll.15s="pollRooms">
    <div class="border-b border-[#ebebeb] px-4 py-3">
        <p class="text-sm font-semibold text-[#100f14]">{{ __('web.chat.conversations') }}</p>
        @if ($unreadCount > 0)
            <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-[#99745c] px-1.5 text-[10px] font-bold text-white">
                {{ $unreadCount }}
            </span>
        @endif
    </div>

    <div class="flex-1 overflow-y-auto divide-y divide-[#ebebeb]">
        @forelse ($rooms as $room)
            @php
                $isActive = $activeRoomId === $room->id;
                $label = $room->type->value === 'direct'
                    ? $room->participants->where('user_id', '!=', auth()->id())->first()?->user?->name ?? __('web.chat.unknown_user')
                    : ($room->title ?? $room->courseGroup?->getTranslation('title', app()->getLocale()) ?? __('web.chat.group_room'));
                $lastAt = $room->last_message_at?->diffForHumans();
            @endphp
            <button
                type="button"
                wire:click.prevent="$dispatch('room-selected', { roomId: {{ $room->id }} })"
                data-chat-action="dashboard-select-room"
                data-chat-room-id="{{ $room->id }}"
                class="w-full px-4 py-3 text-start transition-colors hover:bg-[#f9f6f4] {{ $isActive ? 'bg-[#f9f6f4]' : '' }}"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 truncate">
                        <p class="truncate text-sm font-medium text-[#100f14]">{{ $label }}</p>
                        <p class="mt-0.5 truncate text-xs text-[#818181]">
                            {{ $room->courseGroup?->getTranslation('title', app()->getLocale()) }}
                        </p>
                    </div>
                    @if ($lastAt)
                        <span class="shrink-0 text-[10px] text-[#818181]">{{ $lastAt }}</span>
                    @endif
                </div>
            </button>
        @empty
            <div class="px-4 py-8 text-center text-sm text-[#818181]">
                {{ __('web.chat.no_rooms') }}
            </div>
        @endforelse
    </div>
</div>
