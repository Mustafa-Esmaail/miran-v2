<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-4">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between gap-2">
                        <span>{{ __('admin.navigation.chat_rooms') }}</span>
                        <button
                            type="button"
                            wire:click="markAllRoomsAsRead"
                            wire:loading.attr="disabled"
                            class="rounded-lg px-2 py-1 text-[11px] font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                        >{{ __('admin.actions.mark_all_as_read') }}</button>
                    </div>
                </x-slot>

                <div wire:poll.10s class="max-h-[65vh] space-y-2 overflow-y-auto pe-1">
                    @forelse ($rooms as $room)
                        @php
                            $isSelected = (int) $selectedRoom?->id === (int) $room->id;
                            $isDirect = $room->type === \App\Enums\ChatRoomType::Direct;
                            $roomTitle = $isDirect
                                ? ($room->participants->map(fn ($p) => $p->user?->name)->filter()->implode(' & ') ?: ('Room #'.$room->id))
                                : ($room->courseGroup?->getTranslation('title', app()->getLocale())
                                    ?? $room->course?->getTranslation('title', app()->getLocale())
                                    ?? $room->title
                                    ?? ('Room #'.$room->id));
                            $previewMessage = $roomPreviews[$room->id] ?? null;
                            $unreadCount = $unreadCounts[$room->id] ?? 0;
                        @endphp

                        <button
                            type="button"
                            wire:click="selectRoom({{ $room->id }})"
                            class="w-full rounded-xl border px-3 py-3 text-start transition {{ $isSelected ? 'border-green-300 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : ($unreadCount > 0 ? 'border-green-200 bg-white hover:bg-gray-50 dark:border-green-900 dark:bg-gray-900 dark:hover:bg-gray-800' : 'border-gray-200 bg-white hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800') }}"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-1.5">
                                    @if ($isDirect)
                                        <span class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold text-white" style="background:#3b82f6">{{ __('admin.labels.chat_direct') }}</span>
                                    @else
                                        <span class="shrink-0 rounded-full bg-green-600 px-1.5 py-0.5 text-[10px] font-semibold text-white dark:bg-green-700">{{ __('admin.labels.chat_group') }}</span>
                                    @endif
                                    <p class="line-clamp-1 text-sm font-semibold {{ $unreadCount > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-700 dark:text-gray-100' }}">{{ $roomTitle }}</p>
                                </div>
                                <span class="shrink-0 text-[11px] text-gray-500">{{ $room->last_message_at?->format('H:i') ?? '-' }}</span>
                            </div>
                            <p class="mt-1 line-clamp-1 text-xs text-gray-500 dark:text-gray-400">{{ $previewMessage ?: __('admin.empty_states.no_messages') }}</p>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-[11px] text-gray-400">#{{ $room->id }}</span>
                                @if ($unreadCount > 0)
                                    <span class="rounded-full bg-green-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $unreadCount }}</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-400 dark:bg-gray-700 dark:text-gray-500">0</span>
                                @endif
                            </div>
                        </button>
                    @empty
                        <p class="py-8 text-center text-sm text-gray-400">{{ __('admin.empty_states.no_chat_rooms') }}</p>
                    @endforelse
                </div>
            </x-filament::section>

            @if ($selectedRoom && $participants->isNotEmpty())
                <div
                    x-data="{
                        open: false,
                        heading: '',
                        description: '',
                        confirmLabel: '',
                        isDanger: true,
                        userId: null,
                        ask(userId, heading, description, confirmLabel, isDanger) {
                            this.userId = userId;
                            this.heading = heading;
                            this.description = description;
                            this.confirmLabel = confirmLabel;
                            this.isDanger = isDanger;
                            this.open = true;
                        },
                        confirm() {
                            this.$wire.toggleChatBan(this.userId);
                            this.open = false;
                        },
                    }"
                >
                    {{-- Custom confirmation modal --}}
                    <div
                        x-show="open"
                        x-cloak
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 z-50 flex items-center justify-center p-4"
                        role="dialog"
                        aria-modal="true"
                    >
                        {{-- Backdrop --}}
                        <div class="absolute inset-0 bg-gray-950/60 backdrop-blur-sm" @click="open = false"></div>

                        {{-- Panel --}}
                        <div
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 translate-y-1"
                            class="relative z-9999 w-full max-w-sm rounded-2xl  bg-white p-6 shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
                            @click.stop
                            @keydown.escape.window="open = false"
                        >
                            {{-- Icon --}}
                            <div
                                class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full"
                                :class="isDanger ? 'bg-red-50 dark:bg-red-500/10' : 'bg-green-50 dark:bg-green-500/10'"
                            >
                                <template x-if="isDanger">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                </template>
                                <template x-if="!isDanger">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </template>
                            </div>

                            {{-- Text --}}
                            <h3
                                class="mb-1 text-center text-base font-semibold text-gray-950 dark:text-white"
                                x-text="heading"
                            ></h3>
                            <p
                                class="text-center text-sm text-gray-500 dark:text-gray-400"
                                x-text="description"
                            ></p>

                            {{-- Buttons --}}
                            <div class="mt-6 flex gap-3">
                                <button
                                    type="button"
                                    @click="open = false"
                                    class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                >{{ __('admin.actions.cancel') }}</button>
                                <button
                                    type="button"
                                    @click="confirm()"
                                    class="flex-1 rounded-lg px-4 py-2 text-sm font-semibold shadow-sm transition"
                                    :class="isDanger
                                        ? 'bg-red-600 hover:bg-red-500 dark:bg-red-500 dark:hover:bg-red-400 text-red'
                                        : 'bg-green-600 hover:bg-green-500 dark:bg-green-500 dark:hover:bg-green-400 text-white ' "
                                    x-text="confirmLabel"
                                ></button>
                            </div>
                        </div>
                    </div>

                    <x-filament::section>
                        <x-slot name="heading">{{ __('admin.sections.participants') }} ({{ $participants->count() }})</x-slot>

                        <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($participants as $participant)
                                <li class="flex items-center justify-between gap-2 py-2 text-sm">
                                    <div class="flex min-w-0 flex-col">
                                        <span class="truncate font-medium">{{ $participant->user?->name }}</span>
                                        <span class="text-xs text-gray-500">{{ $participant->role_in_room->value }}</span>
                                    </div>

                                    @if ($participant->user && auth('admin')->user()?->can('users.chat_ban'))
                                        @if ($participant->user->is_chat_banned)
                                            <button
                                                type="button"
                                                @click="ask(
                                                    {{ $participant->user->id }},
                                                    {{ Js::from(__('admin.confirmations.unban_from_chat', ['name' => $participant->user->name])) }},
                                                    {{ Js::from(__('admin.confirmations.unban_from_chat_description')) }},
                                                    {{ Js::from(__('admin.actions.unban_chat')) }},
                                                    false
                                                )"
                                                class="shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900/60"
                                            >{{ __('admin.labels.chat_banned') }} &middot; {{ __('admin.actions.unban_chat') }}</button>
                                        @else
                                            <button
                                                type="button"
                                                @click="ask(
                                                    {{ $participant->user->id }},
                                                    {{ Js::from(__('admin.confirmations.ban_from_chat', ['name' => $participant->user->name])) }},
                                                    {{ Js::from(__('admin.confirmations.ban_from_chat_description')) }},
                                                    {{ Js::from(__('admin.actions.ban_chat')) }},
                                                    true
                                                )"
                                                class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 hover:bg-red-100 hover:text-red-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-red-900/40 dark:hover:text-red-300"
                                            >{{ __('admin.actions.ban_chat') }}</button>
                                        @endif
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </x-filament::section>
                </div>
            @endif
        </div>

        <div class="xl:col-span-8">
            <x-filament::section>
                @if (! $selectedRoom)
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-8 text-center dark:border-gray-700 dark:bg-gray-900">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.empty_states.no_chat_rooms') }}</p>
                    </div>
                @else
                    <x-slot name="heading">
                        <span>{{ __('admin.sections.messages') }}</span>
                        <span class="ms-1 text-xs font-normal text-gray-400">({{ $totalMessages }})</span>
                    </x-slot>

                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        @php
                            $isSelectedDirect = $selectedRoom->type === \App\Enums\ChatRoomType::Direct;
                            $headerTitle = $isSelectedDirect
                                ? ($participants->map(fn ($p) => $p->user?->name)->filter()->implode(' & ') ?: ('Room #'.$selectedRoom->id))
                                : ($selectedRoom->courseGroup?->getTranslation('title', app()->getLocale())
                                    ?? $selectedRoom->course?->getTranslation('title', app()->getLocale())
                                    ?? $selectedRoom->title
                                    ?? ('Room #'.$selectedRoom->id));
                            $headerSubtitle = $isSelectedDirect
                                ? __('admin.labels.chat_direct')
                                : ($selectedRoom->course?->getTranslation('title', app()->getLocale()) ?? '-');
                        @endphp
                        <div class="border-b border-green-100 bg-gradient-to-r from-green-600 to-emerald-500 px-4 py-3 text-white dark:border-green-800">
                            <p class="text-sm font-semibold">{{ $headerTitle }}</p>
                            <p class="text-xs text-green-50">{{ $headerSubtitle }}</p>
                        </div>

                        <div
                            id="admin-chat-messages"
                            wire:poll.2s="refreshMessages"
                            class="h-[55vh] space-y-2 overflow-y-auto bg-[radial-gradient(circle_at_top,_rgba(34,197,94,0.08),_transparent_55%)] px-3 py-4"
                            x-data
                            x-init="$el.scrollTop = $el.scrollHeight"
                            @admin-chat-scroll.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                            style="min-height: 360px;"
                        >
                            @forelse ($messages as $message)
                                @php
                                    $isMine = (int) $message->sender_user_id === (int) auth('admin')->id();
                                @endphp

                                <div wire:key="admin-chat-message-{{ $message->id }}" class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                    <div x-data="{ hiding: false, hideReason: '' }" class="w-full max-w-[84%] rounded-2xl px-3 py-2 shadow-sm {{ $message->isHidden() ? 'border border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/60' : ($isMine ? 'bg-green-100 dark:bg-green-900/40' : 'bg-white dark:bg-gray-800') }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-xs font-semibold text-gray-700 dark:text-gray-200">{{ $message->sender?->name ?? __('admin.labels.unknown') }}</span>
                                            <span class="text-[11px] text-gray-500">{{ $message->created_at->format('d/m/Y H:i') }}</span>
                                        </div>

                                        @if ($message->replyTo)
                                            <div class="mt-1 rounded-md border-s-2 border-gray-300 bg-black/5 px-2 py-1 text-xs text-gray-600 dark:border-gray-600 dark:bg-white/5 dark:text-gray-300">
                                                <span class="font-medium">{{ $message->replyTo->sender?->name }}:</span>
                                                {{ Str::limit($message->replyTo->body, 100) }}
                                            </div>
                                        @endif

                                        <p class="mt-1 whitespace-pre-wrap text-sm text-gray-800 dark:text-gray-100">{{ $message->body }}</p>

                                        @if ($message->isHidden())
                                            <p class="mt-1 text-xs text-red-500">{{ __('admin.labels.hidden_by') }}: {{ $message->hiddenBy?->name }} - {{ $message->hidden_reason }}</p>
                                        @endif

                                        <div class="mt-2 flex items-center justify-end gap-3 text-xs">
                                            @if ($message->isHidden())
                                                <button type="button" wire:click="unhideMessage({{ $message->id }})" wire:loading.attr="disabled" class="text-green-600 hover:text-green-700 dark:text-green-400">{{ __('admin.actions.unhide_message') }}</button>
                                            @else
                                                <button type="button" @click="hiding = !hiding" class="text-orange-500 hover:text-orange-600 dark:text-orange-400" x-text="hiding ? '{{ __('admin.actions.cancel') }}' : '{{ __('admin.actions.hide_message') }}'"></button>
                                            @endif

                                            @if (! $selectedRoom->isReadOnly())
                                                <button type="button" wire:click="setReply({{ $message->id }})" class="text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">{{ __('admin.actions.reply') }}</button>
                                            @endif
                                        </div>

                                        <div x-show="hiding" x-cloak class="mt-2 flex items-center gap-2">
                                            <input
                                                x-model="hideReason"
                                                type="text"
                                                placeholder="{{ __('admin.labels.hide_reason') }}"
                                                @keydown.enter.prevent="if (hideReason.trim()) { $wire.hideMessage({{ $message->id }}, hideReason); hiding = false; hideReason = ''; }"
                                                class="w-full rounded-lg border border-gray-300 bg-white px-2 py-1 text-xs focus:border-orange-400 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                            />
                                            <button
                                                type="button"
                                                @click="if (hideReason.trim()) { $wire.hideMessage({{ $message->id }}, hideReason); hiding = false; hideReason = ''; }"
                                                class="rounded-lg bg-orange-500 px-2 py-1 text-xs font-medium text-white hover:bg-orange-600"
                                            >{{ __('admin.actions.confirm') }}</button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="py-8 text-center text-sm text-gray-400">{{ __('admin.empty_states.no_messages') }}</p>
                            @endforelse
                        </div>

                        <div class="border-t border-gray-100 bg-white px-3 py-3 dark:border-gray-700 dark:bg-gray-900">
                            @if ($selectedRoom->isReadOnly())
                                <p class="text-center text-sm text-gray-400">{{ __('admin.labels.room_read_only') }}</p>
                            @else
                                @if ($replyToMessage)
                                    <div class="mb-2 flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 dark:bg-gray-800">
                                        <span><span class="font-medium">{{ __('admin.labels.reply_to') }}:</span> {{ $replyToMessage->sender?->name }} - {{ Str::limit($replyToMessage->body, 60) }}</span>
                                        <button type="button" wire:click="clearReply" class="ms-2 text-gray-400 hover:text-gray-600">&times;</button>
                                    </div>
                                @endif

                                <form wire:submit.prevent="sendMessage" class="grid grid-cols-4 gap-2">
                                    <textarea wire:model.live="body" rows="2" placeholder="{{ __('admin.labels.type_message') }}" class="col-span-3 w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea>
                                    <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage" class="col-span-1 min-h-[56px] rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ __('admin.actions.send_message') }}</button>
                                </form>

                                @error('body')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
