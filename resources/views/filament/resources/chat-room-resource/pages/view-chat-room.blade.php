<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <div class="space-y-4 xl:col-span-4">
            <x-filament::section>
                <x-slot name="heading">{{ __('admin.sections.room_details') }}</x-slot>

                <dl class="space-y-3 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.labels.type') }}</dt>
                        <dd class="font-medium">
                            {{ $record->type->value === 'course_group' ? __('admin.labels.chat_group') : __('admin.labels.chat_direct') }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.labels.course') }}</dt>
                        <dd class="text-end font-medium">{{ $record->course?->getTranslation('title', app()->getLocale()) ?? '-' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.labels.course_group') }}</dt>
                        <dd class="text-end font-medium">{{ $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-' }}</dd>
                    </div>
                    <div class="flex items-start justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.labels.status') }}</dt>
                        <dd>
                            @if ($record->isReadOnly())
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ __('admin.labels.archived') }}</span>
                            @else
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700 dark:bg-green-900 dark:text-green-300">{{ __('admin.labels.active') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-2">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('admin.labels.last_message_at') }}</dt>
                        <dd class="font-medium">{{ $record->last_message_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('admin.sections.participants') }} ({{ $participants->count() }})</x-slot>

                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($participants as $participant)
                        <li class="flex items-center justify-between gap-2 py-2 text-sm">
                            <div class="flex min-w-0 flex-col">
                                <span class="truncate font-medium">{{ $participant->user?->name }}</span>
                                <span class="text-xs text-gray-500">{{ $participant->role_in_room->value }}</span>
                            </div>

                            @if ($participant->role_in_room === \App\Enums\RoleInRoom::Student && $participant->user)
                                @if ($participant->user->is_chat_banned)
                                    <button
                                        type="button"
                                        wire:click="toggleChatBan({{ $participant->user->id }})"
                                        wire:confirm="{{ __('admin.confirmations.unban_from_chat', ['name' => $participant->user->name]) }}"
                                        wire:loading.attr="disabled"
                                        class="shrink-0 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 hover:bg-red-200 dark:bg-red-900/40 dark:text-red-300 dark:hover:bg-red-900/60"
                                    >{{ __('admin.labels.chat_banned') }} &middot; {{ __('admin.actions.unban_chat') }}</button>
                                @else
                                    <button
                                        type="button"
                                        wire:click="toggleChatBan({{ $participant->user->id }})"
                                        wire:confirm="{{ __('admin.confirmations.ban_from_chat', ['name' => $participant->user->name]) }}"
                                        wire:loading.attr="disabled"
                                        class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 hover:bg-red-100 hover:text-red-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-red-900/40 dark:hover:text-red-300"
                                    >{{ __('admin.actions.ban_chat') }}</button>
                                @endif
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        </div>

        <div class="xl:col-span-8">
            <x-filament::section>
                <x-slot name="heading">{{ __('admin.sections.messages') }} <span class="ms-1 text-xs font-normal text-gray-400">({{ $totalMessages }})</span></x-slot>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="border-b border-green-100 bg-gradient-to-r from-green-600 to-emerald-500 px-4 py-3 text-white dark:border-green-800">
                        <p class="text-sm font-semibold">{{ $record->title ?: __('admin.sections.messages') }}</p>
                        <p class="text-xs text-green-50">{{ $record->courseGroup?->getTranslation('title', app()->getLocale()) ?? '-' }}</p>
                    </div>

                    <div
                        id="admin-chat-messages"
                        wire:poll.2s="refreshMessages"
                        class="space-y-2 bg-[radial-gradient(circle_at_top,_rgba(34,197,94,0.08),_transparent_55%)] px-3 py-4"
                        x-data
                        x-init="$el.scrollTop = $el.scrollHeight"
                        @admin-chat-scroll.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
                        style="max-height: 540px; overflow-y: auto;"
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

                                        @if (! $record->isReadOnly())
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
                        @if ($record->isReadOnly())
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
                                <button type="submit" wire:loading.attr="disabled" class="col-span-1 min-h-[56px] rounded-xl bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">{{ __('admin.actions.send_message') }}</button>
                            </form>

                            @error('body')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
