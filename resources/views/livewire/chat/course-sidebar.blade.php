<section class="flex h-full min-h-[420px] flex-col overflow-hidden rounded-[24px] border border-[var(--border-subtle)] bg-white" wire:poll.15s="pollSidebar">
    <div class="border-b border-[var(--divider-soft)] px-4 py-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-[var(--text-ink)]">{{ __('web.chat.title') }}</p>
                <p class="mt-1 text-xs text-[var(--text-subtle)]">{{ __('web.chat.conversations') }}</p>
            </div>
            @if ($activeRoom)
                <a
                    href="{{ route('dashboard.messages.room', $activeRoom->id) }}"
                    class="rounded-full bg-[var(--nav-brand-dark)] px-3 py-1.5 text-[11px] font-semibold text-white transition hover:opacity-95">
                    {{ __('web.chat.open') }}
                </a>
            @endif
        </div>
    </div>

    <div class="border-b border-[var(--divider-soft)] px-3 py-3">
        <div class="flex flex-wrap gap-2">
            @forelse ($rooms as $room)
                @php
                    $isActive = $activeRoom?->id === $room->id;
                    $label = $room->type->value === 'direct'
                        ? $room->participants->where('user_id', '!=', auth()->id())->first()?->user?->name ?? __('web.chat.unknown_user')
                        : ($room->title ?? $room->courseGroup?->getTranslation('title', app()->getLocale()) ?? __('web.chat.group_room'));
                @endphp
                <button
                    type="button"
                    wire:click="selectRoom({{ $room->id }})"
                    @class([
                        'rounded-full px-3 py-2 text-xs font-semibold transition',
                        'bg-[var(--nav-active-soft)] text-[var(--accent-500)]' => $isActive,
                        'bg-[var(--page-muted-bg)] text-[var(--text-subtle)] hover:bg-[var(--nav-hover-soft)]' => ! $isActive,
                    ])>
                    {{ $label }}
                </button>
            @empty
                <p class="text-xs text-[var(--text-subtle)]">{{ __('web.chat.no_rooms') }}</p>
            @endforelse
        </div>
    </div>

    @if ($activeRoom)
        <div class="flex-1 overflow-y-auto px-4 py-4">
            <div class="space-y-3">
                @forelse ($messages as $message)
                    @php
                        $isMine = $message->sender_user_id === auth()->id();
                    @endphp
                    <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[88%]">
                            @if (! $isMine)
                                <p class="mb-1 text-[11px] font-medium text-[var(--text-subtle)]">{{ $message->sender?->name }}</p>
                            @endif
                            @if ($message->replyTo)
                                <div class="mb-1 rounded-xl border-s-2 border-[var(--accent-500)] bg-[var(--page-muted-bg)] px-2 py-1 text-[10px] text-[var(--text-subtle)]">
                                    {{ Str::limit($message->replyTo->body, 60) }}
                                </div>
                            @endif
                            <div
                                @class([
                                    'rounded-2xl px-3 py-2 text-xs leading-5',
                                    'bg-[var(--accent-500)] text-white' => $isMine,
                                    'bg-[var(--page-muted-bg)] text-[var(--text-ink)]' => ! $isMine,
                                ])>
                                {{ $message->body }}
                            </div>
                            <div class="mt-1 flex items-center gap-2 {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                <span class="text-[10px] text-[var(--text-subtle)]">{{ $message->created_at->format('H:i') }}</span>
                                @if (! $isMine && ! $activeRoom->isReadOnly())
                                    <button type="button" wire:click="setReply({{ $message->id }})" class="text-[10px] font-semibold text-[var(--accent-500)]">
                                        {{ __('web.chat.reply') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl bg-[var(--page-muted-bg)] px-4 py-6 text-center text-xs text-[var(--text-subtle)]">
                        {{ __('web.chat.no_messages') }}
                    </div>
                @endforelse
            </div>
        </div>

        <div class="border-t border-[var(--divider-soft)] px-4 py-3">
            @if ($replyToMessageId)
                <div class="mb-2 flex items-center justify-between rounded-xl bg-[var(--page-muted-bg)] px-3 py-2 text-[10px] text-[var(--text-subtle)]">
                    <span>{{ __('web.chat.replying') }}</span>
                    <button type="button" wire:click="clearReply" class="font-semibold text-[var(--accent-500)]">
                        ×
                    </button>
                </div>
            @endif

            @if (! $activeRoom->isReadOnly())
                <form wire:submit.prevent="send" class="space-y-2">
                    <textarea
                        wire:model.live="body"
                        rows="3"
                        placeholder="{{ __('web.chat.type_message') }}"
                        class="w-full resize-none rounded-2xl border border-[var(--border-subtle)] bg-[var(--page-muted-bg)] px-3 py-3 text-sm text-[var(--text-ink)] placeholder-[var(--text-subtle)] focus:border-[var(--accent-500)] focus:outline-none"></textarea>
                    <button
                        type="submit"
                        class="w-full rounded-full bg-[var(--nav-brand-dark)] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-95"
                        wire:loading.attr="disabled">
                        {{ __('web.chat.send') }}
                    </button>
                </form>
            @else
                <div class="rounded-2xl bg-[var(--page-muted-bg)] px-4 py-3 text-center text-xs text-[var(--text-subtle)]">
                    {{ __('web.chat.read_only') }}
                </div>
            @endif
        </div>
    @endif
</section>
