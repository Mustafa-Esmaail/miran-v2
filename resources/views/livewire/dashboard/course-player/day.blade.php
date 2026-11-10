<x-account-shell active="courses" :title="$course['title']" :nav-links="$navLinks" :show-header="false">
    <x-slot:hero>
        <x-course-player-banner :course="$course" />
    </x-slot:hero>

    <div class="space-y-6">
        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-muted pb-4">
                <div>
                    <a href="{{ route('dashboard.course-player', [$course['slug'], $course['group_id']]) }}" class="text-xs font-semibold text-[#99745c]">
                        {{ __('web.course_player.overview') }}
                    </a>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <h2 class="text-2xl font-semibold text-primary">{{ $day['title'] }}</h2>
                        @if (($day['state'] ?? 'available') === 'completed')
                            <span class="rounded-full bg-[#e7f4ed] px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#1f7a4c]">
                                {{ __('web.course_player.state_completed') }}
                            </span>
                        @endif
                    </div>
                    @if ($day['description'])
                        <div class="rich-content mt-2 text-sm text-secondary">
                            {!! $day['description'] !!}
                        </div>
                    @endif
                </div>
                <span class="rounded-full bg-elevated px-3 py-1 text-xs font-semibold text-primary">
                    {{ __('web.course_player.section_summary', ['count' => $items->count()]) }}
                </span>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($items as $item)
                    @php
                        $isAttachment = $item['type'] === 'attachment';
                        $iconClasses = match ($item['type']) {
                            'lesson' => 'bg-[#eef3f2] text-[#374746]',
                            'session' => 'bg-[#f6efe8] text-[#99745c]',
                            'quiz' => 'bg-[#eef0f9] text-[#5b62c3]',
                            default => 'bg-[#eef0f9] text-[#5b62c3]',
                        };
                        $itemState = $item['state'] ?? 'available';
                        $isLocked = (bool) ($item['is_locked'] ?? false);
                        $isCompleted = (bool) ($item['is_completed'] ?? false);
                    @endphp

                    @if ($isAttachment)
                        <x-course-player-attachment-card :item="$item" />
                    @else
                        <a href="{{ $item['route'] }}"
                            @if ($item['open_in_new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif
                            class="group flex min-h-[200px] flex-col rounded-[32px] border border-default bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-[#374746] hover:bg-[#374746] hover:text-white hover:shadow-[0_18px_40px_rgba(16,15,20,0.18)]">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl {{ $iconClasses }} transition group-hover:bg-white/12 group-hover:text-white">
                                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="currentColor" aria-hidden="true">
                                    {!! $item['icon'] !!}
                                </svg>
                            </div>

                            <div class="mt-5 space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-base font-semibold text-primary transition group-hover:text-white">{{ $item['title'] }}</p>
                                    @if ($isCompleted)
                                        <span class="rounded-full bg-[#e7f4ed] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#1f7a4c] transition group-hover:bg-white/12 group-hover:text-white">
                                            {{ __('web.course_player.state_completed') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-muted transition group-hover:text-white/80">{{ $item['meta'] }}</p>
                            </div>

                            <div class="mt-auto pt-5">
                                <span class="rounded-full bg-elevated px-3 py-1 text-xs font-semibold text-primary transition group-hover:bg-white/12 group-hover:text-white">
                                    @if ($itemState === 'completed')
                                        {{ __('web.course_player.state_completed') }}
                                    @else
                                        {{ $item['meta'] }}
                                    @endif
                                </span>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    </div>
</x-account-shell>
