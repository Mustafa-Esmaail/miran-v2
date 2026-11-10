@php
    /** @var \App\Models\User|null $adminUser */
    $adminUser = auth('admin')->user();
    $unreadChatCount = $adminUser
        ? $adminUser->unreadNotifications()
            ->where('type', \App\Notifications\NewChatMessageNotification::class)
            ->count()
        : 0;
    $chatRoomsUrl = $adminUser?->can('chat_rooms.viewAny')
        ? \App\Filament\Admin\Resources\ChatRooms\ChatRoomResource::getUrl('index')
        : null;
@endphp

<div class="rounded-2xl border border-default bg-surface p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="space-y-1">
            <h2 class="text-base font-semibold text-primary-700">{{ __('admin.navigation.chat_rooms') }}</h2>
            <p class="text-sm text-muted">{{ __('admin.sections.messages') }}</p>
        </div>

        <div class="inline-flex items-center gap-2 rounded-lg border border-default bg-elevated px-3 py-2">
            <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-5 w-5 text-primary-600" />
            <span class="text-sm font-semibold text-primary-700">
                {{ $unreadChatCount }}
            </span>
        </div>
    </div>

    <div class="mt-5">
        @if ($chatRoomsUrl)
            <a
                href="{{ $chatRoomsUrl }}"
                class="inline-flex items-center gap-2 rounded-lg border border-default bg-elevated px-4 py-2 text-sm font-medium text-primary-700 transition hover:border-primary-300 hover:bg-primary-50/60 hover:text-primary-600"
            >
                <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-4 w-4" />
                {{ __('admin.navigation.chat_rooms') }}
            </a>
        @else
            <span class="inline-flex items-center gap-2 rounded-lg border border-default bg-gray-100 px-4 py-2 text-sm font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-lock-closed" class="h-4 w-4" />
                {{ __('admin.navigation.chat_rooms') }}
            </span>
        @endif
    </div>
</div>
