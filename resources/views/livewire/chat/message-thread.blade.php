<div class="flex h-full flex-col" data-chat-debug-root="thread" wire:poll.15s="pollMessages">
    @if ($room)
        {{-- Header --}}
        <div class="flex items-center gap-3 border-b border-[#ebebeb] px-5 py-3">
            <div>
                <p class="text-sm font-semibold text-[#100f14]">
                    @if ($room->type->value === 'direct')
                        {{ $room->participants->where('user_id', '!=', auth()->id())->first()?->user?->name ?? __('web.chat.unknown_user') }}
                    @else
                        {{ $room->title ?? $room->courseGroup?->getTranslation('title', app()->getLocale()) }}
                    @endif
                </p>
                <p class="text-xs text-[#818181]">{{ $room->course?->getTranslation('title', app()->getLocale()) }}</p>
            </div>
        </div>

        {{-- Messages --}}
        <div
            class="flex-1 overflow-y-auto px-5 py-4 flex flex-col gap-3"
            x-data="{ atBottom: true }"
            x-on:scroll.passive="atBottom = ($el.scrollHeight - $el.scrollTop - $el.clientHeight) < 60"
            x-init="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
            x-on:thread-messages-polled.window="if (atBottom) $nextTick(() => $el.scrollTop = $el.scrollHeight)"
            x-on:thread-scroll-bottom.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
        >
            @if ($messages && $messages->isNotEmpty())
                @foreach ($messages as $message)
                    @php
                        $isMine = $message->sender_user_id === auth()->id();
                        $isHidden = $message->isHidden();
                    @endphp
                    <div wire:key="msg-{{ $message->id }}" class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[75%] {{ $isHidden ? 'opacity-50' : '' }}">
                            @if (! $isMine)
                                <p class="mb-1 text-xs font-medium text-[#818181]">{{ $message->sender?->name }}</p>
                            @endif

                            @if ($message->replyTo)
                                <div class="mb-1 rounded border-l-2 border-[#99745c] bg-[#f9f6f4] px-2 py-1 text-xs text-[#818181]">
                                    {{ Str::limit($message->replyTo->body, 80) }}
                                </div>
                            @endif

                            <div class="rounded-2xl px-4 py-2 text-sm {{ $isMine ? 'bg-[#99745c] text-white' : 'bg-[#f5f5f6] text-[#100f14]' }}">
                                {{ $message->body }}
                            </div>

                            <div class="mt-0.5 flex items-center gap-2 {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <span class="text-[10px] text-[#818181]">{{ $message->created_at->format('H:i') }}</span>
                                @if ($isHidden)
                                    <span class="text-[10px] text-[#818181]">[{{ __('web.chat.hidden') }}]</span>
                                @endif
                                @if (! $isMine && ! $room->isReadOnly())
                                    <button type="button" wire:click.prevent="setReply({{ $message->id }})" data-chat-action="thread-set-reply" class="text-[10px] text-[#818181] hover:text-[#99745c]">
                                        {{ __('web.chat.reply') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="flex h-full items-center justify-center text-sm text-[#818181]">
                    {{ __('web.chat.no_messages') }}
                </div>
            @endif
        </div>

        {{-- Composer --}}
        @if (! $room->isReadOnly())
            <div class="border-t border-[#ebebeb] px-4 py-3">
                @if ($replyToMessageId)
                    <div class="mb-2 flex items-center justify-between rounded-lg bg-[#f9f6f4] px-3 py-1.5 text-xs text-[#818181]">
                        <span>{{ __('web.chat.replying') }}</span>
                        <button type="button" wire:click.prevent="clearReply" data-chat-action="thread-clear-reply" class="ml-2 text-[#818181] hover:text-[#100f14]">&times;</button>
                    </div>
                @endif
                <form wire:submit.prevent="send" data-chat-form="thread-send" class="flex items-end gap-3">
                    <textarea
                        wire:model.live="body"
                        rows="2"
                        placeholder="{{ __('web.chat.type_message') }}"
                        class="flex-1 resize-none rounded-xl border border-[#ebebeb] bg-[#f9f6f4] px-4 py-2.5 text-sm text-[#100f14] placeholder-[#818181] focus:border-[#99745c] focus:outline-none"
                    ></textarea>
                    <button
                        type="submit"
                        class="shrink-0 rounded-xl bg-[#99745c] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#99745c]/90 disabled:opacity-50"
                        wire:loading.attr="disabled"
                    >
                        {{ __('web.chat.send') }}
                    </button>
                </form>
            </div>
        @else
            <div class="border-t border-[#ebebeb] px-4 py-3 text-center text-xs text-[#818181]">
                {{ __('web.chat.read_only') }}
            </div>
        @endif
    @endif
</div>
