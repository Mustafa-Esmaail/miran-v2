<x-account-shell active="messages" :title="__('web.chat.title')">
    <div class="flex h-[calc(100vh-200px)] min-h-125 overflow-hidden rounded-2xl border border-[#ebebeb] bg-white">
        {{-- Left: room list --}}
        <div class="w-full shrink-0 border-r border-[#ebebeb] md:block md:w-72 lg:w-80 {{ $mobileThreadVisible ? 'hidden' : 'block' }}">
            @livewire('chat.room-list', ['activeRoomId' => $activeRoom?->id], key('room-list'))
        </div>

        {{-- Right: message thread --}}
        <div class="flex-1 flex-col md:flex {{ $mobileThreadVisible ? 'flex' : 'hidden' }}">
            <button
                type="button"
                wire:click="hideMobileThread"
                class="flex items-center gap-1.5 border-b border-[#ebebeb] px-4 py-3 text-sm font-medium text-[#818181] hover:text-[#100f14] md:hidden"
            >
                <span aria-hidden="true">&larr;</span>
                {{ __('web.chat.back_to_list') }}
            </button>
            @if ($activeRoom)
                @livewire('chat.message-thread', ['roomId' => $activeRoom->id], key('thread-'.$activeRoom->id))
            @else
                <div class="flex h-full items-center justify-center text-sm text-[#818181]">
                    {{ __('web.chat.select_room') }}
                </div>
            @endif
        </div>
    </div>
</x-account-shell>
