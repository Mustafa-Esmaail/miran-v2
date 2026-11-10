<nav class="flex flex-col gap-1">
    @foreach ($links as $link)
        @php
            $linkState = $link['state'] ?? 'available';
            $isDisabled = (bool) ($link['is_disabled'] ?? false);
        @endphp

        @if (($link['has_children'] ?? false) === true)
            <details class="group flex flex-col gap-1" @if ($link['is_active']) open @endif>
                <summary
                    @class([
                        'flex list-none items-center gap-3 rounded-full px-3.5 py-3 text-sm font-semibold transition',
                        'cursor-pointer' => !$isDisabled,
                        'bg-[var(--nav-active-soft)] text-[var(--accent-500)]' => $link['is_active'],
                        'text-[var(--icon-muted)] hover:bg-[var(--nav-hover-soft)]' => !$link['is_active'] && !$isDisabled,
                        'cursor-not-allowed text-[var(--icon-muted)] opacity-60' => $isDisabled,
                    ])>
                    <svg viewBox="0 0 24 24" class="h-6 w-6 text-current" fill="currentColor" aria-hidden="true">
                        {!! $link['icon'] !!}
                    </svg>
                    <span class="flex-1">{{ $link['label'] }}</span>
                    @if (($link['badge_count'] ?? 0) > 0)
                        <span data-dashboard-nav-badge="{{ $link['key'] }}" class="inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-[var(--accent-500)] px-1.5 text-[10px] font-semibold leading-none text-white">
                            {{ $link['badge_count'] > 99 ? '99+' : $link['badge_count'] }}
                        </span>
                    @endif
                    @if ($linkState === 'locked')
                        <span class="rounded-full bg-black/5 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]">
                            {{ __('web.course_player.state_locked') }}
                        </span>
                    @elseif ($linkState === 'completed')
                        <span class="rounded-full bg-[#e7f4ed] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#1f7a4c]">
                            {{ __('web.course_player.state_completed') }}
                        </span>
                    @endif
                    <svg class="h-4 w-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.126l3.71-3.894a.75.75 0 1 1 1.08 1.04l-4.25 4.46a.75.75 0 0 1-1.08 0l-4.25-4.46a.75.75 0 0 1 .02-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </summary>
                <div class="ms-7 flex flex-col gap-1 ps-2">
                    @foreach ($link['children'] as $child)
                        @php
                            $childState = $child['state'] ?? 'available';
                            $childDisabled = (bool) ($child['is_disabled'] ?? false);
                        @endphp

                        @if ($childDisabled)
                            <div
                                class="flex cursor-not-allowed items-center gap-3 rounded-full px-3.5 py-2.5 text-sm font-semibold text-[var(--icon-muted)] opacity-60">
                                @if (!empty($child['icon']))
                                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-current" fill="currentColor" aria-hidden="true">
                                        {!! $child['icon'] !!}
                                    </svg>
                                @endif
                                <span class="flex-1">{{ $child['label'] }}</span>
                                <span class="rounded-full bg-black/5 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]">
                                    {{ $childState === 'completed' ? __('web.course_player.state_completed') : __('web.course_player.state_locked') }}
                                </span>
                            </div>
                        @else
                            <a href="{{ $child['route'] }}"
                                @if ($child['open_in_new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif
                                @class([
                                    'flex items-center gap-3 rounded-full px-3.5 py-2.5 text-sm font-semibold transition',
                                    'bg-[var(--nav-active-soft)] text-[var(--accent-500)]' => $child['is_active'],
                                    'text-[var(--icon-muted)] hover:bg-[var(--nav-hover-soft)]' => !$child['is_active'],
                                ])>
                                @if (!empty($child['icon']))
                                    <svg viewBox="0 0 24 24" class="h-5 w-5 text-current" fill="currentColor" aria-hidden="true">
                                        {!! $child['icon'] !!}
                                    </svg>
                                @endif
                                <span class="flex-1">{{ $child['label'] }}</span>
                                @if ($childState === 'completed')
                                    <span class="rounded-full bg-[#e7f4ed] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#1f7a4c]">
                                        {{ __('web.course_player.state_completed') }}
                                    </span>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            </details>
        @else
            @if ($isDisabled)
                <div
                    class="flex cursor-not-allowed items-center gap-3 rounded-full px-3.5 py-3 text-sm font-semibold text-[var(--icon-muted)] opacity-60">
                    <svg viewBox="0 0 24 24" class="h-6 w-6 text-current" fill="currentColor" aria-hidden="true">
                        {!! $link['icon'] !!}
                    </svg>
                    <span class="flex-1">{{ $link['label'] }}</span>
                    <span class="rounded-full bg-black/5 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em]">
                        {{ __('web.course_player.state_locked') }}
                    </span>
                </div>
            @else
                <a href="{{ $link['route'] }}"
                    @if ($link['open_in_new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif
                    @class([
                        'flex items-center gap-3 rounded-full px-3.5 py-3 text-sm font-semibold transition',
                        'bg-[var(--nav-active-soft)] text-[var(--accent-500)]' => $link['is_active'],
                        'text-[var(--icon-muted)] hover:bg-[var(--nav-hover-soft)]' => !$link['is_active'] && !$link['is_delete'],
                        'text-[var(--danger-strong)] hover:bg-[var(--danger-soft)]' => $link['is_delete'],
                    ])>
                    <svg viewBox="0 0 24 24" class="h-6 w-6 text-current" fill="currentColor" aria-hidden="true">
                        {!! $link['icon'] !!}
                    </svg>
                    <span class="flex-1">{{ $link['label'] }}</span>
                    @if (($link['badge_count'] ?? 0) > 0)
                        <span data-dashboard-nav-badge="{{ $link['key'] }}" class="inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-[var(--accent-500)] px-1.5 text-[10px] font-semibold leading-none text-white">
                            {{ $link['badge_count'] > 99 ? '99+' : $link['badge_count'] }}
                        </span>
                    @endif
                    @if ($linkState === 'completed')
                        <span class="rounded-full bg-[#e7f4ed] px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#1f7a4c]">
                            {{ __('web.course_player.state_completed') }}
                        </span>
                    @endif
                </a>
            @endif
        @endif
    @endforeach
</nav>
