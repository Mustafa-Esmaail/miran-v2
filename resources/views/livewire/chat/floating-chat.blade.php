@auth
<div
    class="fixed bottom-6 end-6 z-50 flex flex-col items-end gap-3"
    data-chat-debug-root="floating"
    @if ($isOpen) wire:poll.15s="pollChat" @else wire:poll.30s @endif
>
    {{-- Popup panel --}}
    @if ($isOpen)
        <div class="flex h-130 w-90 flex-col overflow-hidden rounded-2xl border border-[#ebebeb] bg-white shadow-2xl">

            @if ($activeRoom)
                {{-- ── Thread view ──────────────────────────────────── --}}
                <div class="flex items-center gap-2 border-b border-[#ebebeb] px-4 py-3">
                    {{-- Back to conversation list --}}
                    <button
                        type="button"
                        wire:click.prevent="showRooms"
                        data-chat-action="floating-show-rooms"
                        class="shrink-0 text-[#818181] hover:text-[#100f14]"
                        title="{{ __('web.chat.back') }}"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>

                    <p class="flex-1 truncate text-sm font-semibold text-[#100f14]">
                        @if ($activeRoom->type->value === 'direct')
                            {{ $activeRoom->participants->where('user_id', '!=', auth()->id())->first()?->user?->name ?? __('web.chat.unknown_user') }}
                        @else
                            {{ $activeRoom->title ?? $activeRoom->courseGroup?->getTranslation('title', app()->getLocale()) }}
                        @endif
                    </p>

                    {{-- Close popup entirely --}}
                    <button
                        type="button"
                        wire:click.prevent="closeChat"
                        data-chat-action="floating-close-room"
                        class="shrink-0 text-[#818181] hover:text-[#100f14]"
                        title="{{ __('web.chat.close') }}"
                    >&times;</button>
                </div>

                {{-- Messages --}}
                <div
                    id="fc-messages"
                    class="flex flex-1 flex-col gap-2 overflow-y-auto px-4 py-3"
                    x-data="{ atBottom: true }"
                    x-on:scroll.passive="atBottom = ($el.scrollHeight - $el.scrollTop - $el.clientHeight) < 60"
                    x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                    @fc-scroll-bottom.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                    @fc-messages-polled.window="if (atBottom) $nextTick(() => $el.scrollTop = $el.scrollHeight)"
                >
                    @forelse ($messages as $message)
                        @php $isMine = $message->sender_user_id === auth()->id(); @endphp
                        <div wire:key="fc-msg-{{ $message->id }}" class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%]">
                                @if (! $isMine)
                                    <p class="mb-0.5 text-[10px] font-medium text-[#818181]">{{ $message->sender?->name }}</p>
                                @endif
                                @if ($message->replyTo)
                                    <div class="mb-1 rounded border-l-2 border-[#99745c] bg-[#f9f6f4] px-2 py-1 text-[10px] text-[#818181]">
                                        {{ Str::limit($message->replyTo->body, 60) }}
                                    </div>
                                @endif
                                <div class="rounded-2xl px-3 py-2 text-sm {{ $isMine ? 'bg-[#99745c] text-white' : 'bg-[#f5f5f6] text-[#100f14]' }}">
                                    {{ $message->body }}
                                </div>
                                <div class="mt-0.5 flex items-center gap-2 {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                    <span class="text-[10px] text-[#818181]">{{ $message->created_at->format('H:i') }}</span>
                                    @if (! $isMine && ! $activeRoom->isReadOnly())
                                        <button
                                            type="button"
                                            wire:click.prevent="setReply({{ $message->id }})"
                                            data-chat-action="floating-set-reply"
                                            class="text-[10px] text-[#818181] hover:text-[#99745c]"
                                        >{{ __('web.chat.reply') }}</button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full items-center justify-center text-xs text-[#818181]">
                            {{ __('web.chat.no_messages') }}
                        </div>
                    @endforelse
                </div>

                {{-- Composer --}}
                @if (! $activeRoom->isReadOnly())
                    <div class="border-t border-[#ebebeb] px-3 py-2">
                        @if ($isChatBanned)
                            <div class="flex items-end gap-2">
                                <textarea
                                    rows="2"
                                    disabled
                                    placeholder="{{ __('web.chat.banned_from_chat') }}"
                                    class="flex-1 cursor-not-allowed resize-none rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-400 placeholder-red-400"
                                ></textarea>
                                <button
                                    type="button"
                                    disabled
                                    class="shrink-0 cursor-not-allowed rounded-xl bg-gray-200 px-3 py-2 text-xs font-semibold text-gray-400"
                                >{{ __('web.chat.send') }}</button>
                            </div>
                        @else
                            @if ($replyToMessageId)
                                <div class="mb-1.5 flex items-center justify-between rounded bg-[#f9f6f4] px-2 py-1 text-[10px] text-[#818181]">
                                    <span>{{ __('web.chat.replying') }}</span>
                                    <button type="button" wire:click.prevent="clearReply" data-chat-action="floating-clear-reply">&times;</button>
                                </div>
                            @endif
                            <form wire:submit.prevent="send" data-chat-form="floating-send" class="flex items-end gap-2">
                                <textarea
                                    wire:model.live="body"
                                    rows="2"
                                    placeholder="{{ __('web.chat.type_message') }}"
                                    class="flex-1 resize-none rounded-xl border border-[#ebebeb] bg-[#f9f6f4] px-3 py-2 text-sm placeholder-[#818181] focus:border-[#99745c] focus:outline-none"
                                ></textarea>
                                <button
                                    type="submit"
                                    class="shrink-0 rounded-xl bg-[#99745c] px-3 py-2 text-xs font-semibold text-white hover:bg-[#99745c]/90 disabled:opacity-50"
                                    wire:loading.attr="disabled"
                                >{{ __('web.chat.send') }}</button>
                            </form>
                        @endif
                    </div>
                @else
                    <div class="border-t border-[#ebebeb] px-3 py-2 text-center text-[10px] text-[#818181]">
                        {{ __('web.chat.read_only') }}
                    </div>
                @endif

            @else
                {{-- ── Conversation list ────────────────────────────── --}}
                <div class="flex items-center justify-between border-b border-[#ebebeb] px-4 py-3">
                    <p class="text-sm font-semibold text-[#100f14]">{{ __('web.chat.conversations') }}</p>
                    <button
                        type="button"
                        wire:click.prevent="closeChat"
                        data-chat-action="floating-close-list"
                        class="text-[#818181] hover:text-[#100f14]"
                        title="{{ __('web.chat.close') }}"
                    >&times;</button>
                </div>

                <div class="flex-1 divide-y divide-[#ebebeb] overflow-y-auto">
                    @forelse ($rooms as $room)
                        @php
                            $label = $room->type->value === 'direct'
                                ? ($room->participants->where('user_id', '!=', auth()->id())->first()?->user?->name ?? __('web.chat.unknown_user'))
                                : ($room->title ?? $room->courseGroup?->getTranslation('title', app()->getLocale()) ?? __('web.chat.group_room'));
                            $lastAt = $room->last_message_at?->diffForHumans();
                        @endphp
                        <button
                            type="button"
                            wire:click.prevent="openRoom({{ $room->id }})"
                            data-chat-action="floating-open-room"
                            data-chat-room-id="{{ $room->id }}"
                            class="w-full px-4 py-3 text-start transition-colors hover:bg-[#f9f6f4]"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-[#100f14]">{{ $label }}</p>
                                    @if ($room->type->value === 'direct')
                                        <p class="mt-0.5 truncate text-xs text-[#818181]">
                                            {{ $room->courseGroup?->getTranslation('title', app()->getLocale()) }}
                                        </p>
                                    @endif
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
            @endif

        </div>
    @endif

    {{-- Floating toggle button --}}
    <button
        type="button"
        wire:click="toggle"
        data-chat-action="floating-toggle"
        class="relative flex h-14 w-14 items-center justify-center rounded-full bg-[#99745c] text-white shadow-lg transition-colors hover:bg-[#99745c]/90"
        aria-label="{{ __('web.chat.open') }}"
    >
        @if ($unreadCount > 0 && ! $isOpen)
            <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif

        @if ($isOpen)
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
        @endif
    </button>
</div>
@endauth
