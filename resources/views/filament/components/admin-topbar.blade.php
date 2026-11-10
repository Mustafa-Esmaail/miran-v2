@php
    $settings = app(\App\Services\Settings\SettingsService::class);
    $logoUrl = $settings->logoUrl();
    $fallbackLogo = 'https://themiran.net/wp-content/uploads/2025/07/picture_2025-07-10_at_3.03.48_PM.png';
    /** @var \App\Models\User|null $adminUser */
    $adminUser = auth('admin')->user();
    $unreadChatCount = $adminUser
        ? $adminUser
            ->unreadNotifications()
            ->where('type', \App\Notifications\NewChatMessageNotification::class)
            ->count()
        : 0;
@endphp

<div class="flex items-center gap-3 px-2">
    {{-- <img
        src="{{ $logoUrl ?: $fallbackLogo }}"
        alt="{{ $settings->siteTitle(app()->getLocale()) }}"
        class="h-8 w-auto rounded"
    /> --}}
    <a href="{{ \App\Filament\Admin\Resources\ChatRooms\ChatRoomResource::getUrl('index') }}" data-admin-chat-link
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 transition hover:bg-gray-50 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
        title="{{ __('admin.navigation.chat_rooms') }}" aria-label="{{ __('admin.navigation.chat_rooms') }}">
        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
            <path d="M17 4H7a5 5 0 0 0-5 5v11.25a.75.75 0 0 0 1.28.53L6.06 18H17a5 5 0 0 0 5-5V9a5 5 0 0 0-5-5Z" />
            <path opacity="0.45" d="M8 9.5h8m-8 3h5" stroke="currentColor" stroke-linecap="round" stroke-width="1.5" />
        </svg>
        {{-- @if ($unreadChatCount > 0)
            <span
                data-admin-chat-unread-badge
                class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-danger-600 px-1 text-[10px] font-bold leading-none text-white"
            >
                {{ $unreadChatCount > 99 ? '99+' : $unreadChatCount }}
            </span>
        @endif --}}
    </a>



    <livewire:locale-switcher variant="dropdown" />
</div>
