<x-account-shell :title="__('web.notifications.title')" :showNav="true" active="dashboard">
    <div class="flex flex-col gap-6 xl:grid xl:grid-cols-3 xl:items-start">
        <div class="rounded-2xl border border-default bg-surface p-5 xl:col-span-1">
            <p class="text-base font-semibold text-primary">{{ __('web.notifications.preferences_title') }}</p>
            <p class="mt-1 text-xs text-muted">{{ __('web.notifications.preferences_subtitle') }}</p>

            <form wire:submit.prevent="savePreferences" class="mt-4 flex flex-col gap-3">
                <label
                    class="flex items-center justify-between gap-3 rounded-xl border border-default bg-surface px-4 py-3 text-sm">
                    <span>{{ __('web.notifications.pref_email') }}</span>
                    <input type="checkbox" wire:model="emailEnabled" class="h-4 w-4">
                </label>
                <label
                    class="flex items-center justify-between gap-3 rounded-xl border border-default bg-surface px-4 py-3 text-sm">
                    <span>{{ __('web.notifications.pref_in_app') }}</span>
                    <input type="checkbox" wire:model="databaseEnabled" class="h-4 w-4">
                </label>
                <label
                    class="flex items-center justify-between gap-3 rounded-xl border border-default bg-surface px-4 py-3 text-sm">
                    <span>{{ __('web.notifications.pref_announcements') }}</span>
                    <input type="checkbox" wire:model="announcementsEnabled" class="h-4 w-4">
                </label>
                <label
                    class="flex items-center justify-between gap-3 rounded-xl border border-default bg-surface px-4 py-3 text-sm">
                    <span>{{ __('web.notifications.pref_course_updates') }}</span>
                    <input type="checkbox" wire:model="courseUpdatesEnabled" class="h-4 w-4">
                </label>
                <label
                    class="flex items-center justify-between gap-3 rounded-xl border border-default bg-surface px-4 py-3 text-sm">
                    <span>{{ __('web.notifications.pref_comment_replies') }}</span>
                    <input type="checkbox" wire:model="commentRepliesEnabled" class="h-4 w-4">
                </label>

                <button type="submit"
                    class="self-start rounded-3xl bg-primary px-4 py-2 text-xs font-semibold text-white">
                    {{ __('web.notifications.save_preferences') }}
                </button>
            </form>
        </div>

        <div class="space-y-6 xl:col-span-2">
            @forelse ($groupedNotifications as $group)
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-base font-semibold text-primary">{{ $group['label'] }}</p>
                        <span class="text-xs text-muted">{{ count($group['items']) }}</span>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($group['items'] as $notification)
                            @php
                                $cardClasses = 'rounded-xl border border-default bg-surface p-4 transition hover:border-primary/40';
                                if (!empty($notification['is_unread'])) {
                                    $cardClasses .= ' shadow-sm';
                                }
                            @endphp
                            @if ($notification['action_url'])
                                <a href="{{ $notification['action_url'] }}" class="{{ $cardClasses }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-primary">{{ $notification['title'] }}</div>
                                            <div class="mt-1 text-xs text-muted">{{ $notification['message'] }}</div>
                                        </div>
                                        <span class="text-xs text-muted">{{ $notification['created_at'] }}</span>
                                    </div>
                                    <div class="mt-3 inline-flex items-center gap-2 text-xs font-semibold text-primary">
                                        {{ $notification['action_label'] ?? __('web.notifications.actions.view_details') }}
                                    </div>
                                </a>
                            @else
                                <div class="{{ $cardClasses }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-primary">{{ $notification['title'] }}</div>
                                            <div class="mt-1 text-xs text-muted">{{ $notification['message'] }}</div>
                                        </div>
                                        <span class="text-xs text-muted">{{ $notification['created_at'] }}</span>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-default bg-surface p-6 text-center text-sm text-muted">
                    {{ __('web.notifications.empty') }}
                </div>
            @endforelse
        </div>
    </div>
</x-account-shell>
