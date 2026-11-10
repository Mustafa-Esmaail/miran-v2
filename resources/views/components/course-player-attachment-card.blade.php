@props(['item'])

@php
    $isCompleted = (bool) ($item['is_completed'] ?? false);
    $files = $item['files'] ?? [];
@endphp

<div class="flex min-h-[200px] flex-col rounded-[32px] border border-default bg-white p-5 shadow-sm">
    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#eef0f9] text-[#5b62c3]">
        <svg viewBox="0 0 24 24" class="h-7 w-7" fill="currentColor" aria-hidden="true">
            {!! $item['icon'] !!}
        </svg>
    </div>

    <div class="mt-5 space-y-1">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-base font-semibold text-primary">{{ $item['title'] }}</p>
            @if ($isCompleted)
                <span class="rounded-full bg-[#e7f4ed] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#1f7a4c]">
                    {{ __('web.course_player.state_completed') }}
                </span>
            @endif
        </div>
        <p class="text-sm text-muted">{{ $item['meta'] }}</p>
    </div>

    <div class="mt-auto space-y-2 pt-5">
        @forelse ($files as $file)
            <a href="{{ $file['url'] }}"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-center gap-2 rounded-full bg-elevated px-3 py-1.5 text-xs font-semibold text-primary transition hover:bg-[#374746] hover:text-white">
                <svg viewBox="0 0 24 24" class="h-3.5 w-3.5 shrink-0" fill="currentColor" aria-hidden="true">
                    <path d="M12 15.25a.75.75 0 0 1-.53-.22l-3.5-3.5a.75.75 0 1 1 1.06-1.06l2.22 2.22V4.75a.75.75 0 0 1 1.5 0v7.94l2.22-2.22a.75.75 0 1 1 1.06 1.06l-3.5 3.5a.75.75 0 0 1-.53.22ZM4.75 19.25a.75.75 0 0 1 0-1.5h14.5a.75.75 0 0 1 0 1.5H4.75Z" />
                </svg>
                <span class="truncate">{{ $file['name'] }}</span>
            </a>
        @empty
            <span class="text-xs text-muted">{{ __('web.downloads.empty') }}</span>
        @endforelse
    </div>
</div>
