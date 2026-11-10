<x-account-shell active="courses" :title="$course['title']" :nav-links="$navLinks" :show-header="false">
    <x-slot:hero>
        <x-course-player-banner :course="$course" />
    </x-slot:hero>

    <div class="space-y-6">
        @if ($isPendingCertificate)
            {{-- Shown after attending day 1: enrollment is waiting for certificate form --}}
            <div class="flex flex-col gap-4 rounded-[20px] border border-blue-200 bg-blue-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                            <path d="M4 4.5A2.5 2.5 0 0 1 6.5 2h11A2.5 2.5 0 0 1 20 4.5v15a.5.5 0 0 1-.777.416L12 15.101l-7.223 4.815A.5.5 0 0 1 4 19.5v-15Zm9 5.25a.75.75 0 0 0-1.5 0V12H9.25a.75.75 0 0 0 0 1.5H11.5v2.25a.75.75 0 0 0 1.5 0V13.5h2.25a.75.75 0 0 0 0-1.5H13V9.75Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-blue-900">{{ __('web.course_player.pending_cert_title') }}</p>
                        <p class="mt-0.5 text-sm text-blue-700">{{ __('web.course_player.pending_cert_body') }}</p>
                    </div>
                </div>
                <a href="{{ $certificateFormUrl }}"
                    class="shrink-0 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                    {{ __('web.course_player.pending_cert_action') }}
                </a>
            </div>
        @elseif ($certificateRequestStatus?->value === 'rejected')
            {{-- Certificate request was rejected: content locked --}}
            <div class="flex flex-col gap-4 rounded-[20px] border border-red-200 bg-red-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2Zm-.75 5.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm.75 9.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-red-900">{{ __('web.course_player.cert_rejected_title') }}</p>
                        <p class="mt-0.5 text-sm text-red-700">{{ __('web.course_player.cert_rejected_body') }}</p>
                    </div>
                </div>
                <a href="{{ $certificateFormUrl }}"
                    class="shrink-0 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                    {{ __('web.course_player.cert_required_action') }}
                </a>
            </div>
        @elseif (!$hasCertificateRequest)
            {{-- Shown before attending day 1: remind student to submit cert request --}}
            <div class="flex flex-col gap-4 rounded-[20px] border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2Zm-.75 5.25a.75.75 0 0 1 1.5 0v5a.75.75 0 0 1-1.5 0v-5Zm.75 9.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-amber-900">{{ __('web.course_player.cert_required_title') }}</p>
                        <p class="mt-0.5 text-sm text-amber-700">{{ __('web.course_player.cert_required_body') }}</p>
                    </div>
                </div>
                <a href="{{ $certificateFormUrl }}"
                    class="shrink-0 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-600">
                    {{ __('web.course_player.cert_required_action') }}
                </a>
            </div>
        @endif

        <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <p class="text-lg font-semibold text-primary">{{ __('admin.models.course_curriculum_sections') }}</p>
                    <p class="mt-1 text-sm text-muted">{{ __('web.course_player.curriculum') }}</p>
                </div>
                <span class="rounded-full bg-elevated px-3 py-1 text-xs font-semibold text-primary">{{ $days->count() }}</span>
            </div>

            @if ($days->isEmpty())
                <div class="rounded-xl border border-dashed border-default bg-surface p-6 text-center text-sm text-muted">
                    {{ __('web.course_player.no_curriculum') }}
                </div>
            @else
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($days as $day)
                        @php
                            $wrapperClass = 'group flex min-h-[220px] flex-col rounded-[32px] border p-5 shadow-sm transition duration-200';
                        @endphp

                        @if ($day['is_locked'])
                            <div class="{{ $wrapperClass }} cursor-not-allowed border-default bg-[#f7f7f7] opacity-80">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-black/5 text-[#6d747b]">
                                    <svg viewBox="0 0 24 24" class="h-7 w-7" fill="currentColor" aria-hidden="true">
                                        <path d="M12 2a4 4 0 0 0-4 4v2H7.75A2.75 2.75 0 0 0 5 10.75v7.5A2.75 2.75 0 0 0 7.75 21h8.5A2.75 2.75 0 0 0 19 18.25v-7.5A2.75 2.75 0 0 0 16.25 8H16V6a4 4 0 0 0-4-4Zm-2.5 6V6a2.5 2.5 0 0 1 5 0v2h-5Z" />
                                    </svg>
                                </div>
                                <div class="mt-5 space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-lg font-semibold text-primary">{{ $day['title'] }}</p>
                                        <span class="rounded-full bg-black/5 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#6d747b]">
                                            {{ __('web.course_player.state_locked') }}
                                        </span>
                                    </div>
                                    @if ($day['description'])
                                        <div class="rich-content text-sm text-muted">
                                            {!! $day['description'] !!}
                                        </div>
                                    @endif
                                    <p class="text-sm text-muted">
                                        @if ($certificateRequestStatus?->value === 'rejected')
                                            {{ __('web.course_player.cert_rejected_locked_hint') }}
                                        @else
                                            {{ __('web.course_player.cert_required_locked_hint') }}
                                        @endif
                                    </p>
                                </div>
                                <div class="mt-auto flex items-center justify-between pt-5">
                                    <span class="rounded-full bg-elevated px-3 py-1 text-xs font-semibold text-primary">
                                        {{ __('web.course_player.section_summary', ['count' => $day['items_count']]) }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <a href="{{ $day['route'] }}"
                                class="{{ $wrapperClass }} border-default bg-white hover:-translate-y-1 hover:border-[#374746] hover:bg-[#374746] hover:text-white hover:shadow-[0_18px_40px_rgba(16,15,20,0.18)]">
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#f6efe8] text-[#99745c] transition group-hover:bg-white/12 group-hover:text-white">
                                    <svg viewBox="0 0 24 24" class="h-7 w-7" fill="currentColor" aria-hidden="true">
                                        <path d="M4 6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75v10.5A2.75 2.75 0 0 1 17.25 20H6.75A2.75 2.75 0 0 1 4 17.25V6.75Z"></path>
                                        <path opacity="0.4" d="M8 8.75h8M8 12h8M8 15.25h5"></path>
                                    </svg>
                                </div>
                                <div class="mt-5 space-y-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-lg font-semibold text-primary transition group-hover:text-white">{{ $day['title'] }}</p>
                                        @if ($day['is_completed'])
                                            <span class="rounded-full bg-[#e7f4ed] px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-[#1f7a4c] transition group-hover:bg-white/12 group-hover:text-white">
                                                {{ __('web.course_player.state_completed') }}
                                            </span>
                                        @endif
                                    </div>
                                    @if ($day['description'])
                                        <div class="rich-content text-sm text-muted transition group-hover:text-white/80">
                                            {!! $day['description'] !!}
                                        </div>
                                    @endif
                                </div>
                                <div class="mt-auto flex items-center justify-between pt-5">
                                    <span class="rounded-full bg-elevated px-3 py-1 text-xs font-semibold text-primary transition group-hover:bg-white/12 group-hover:text-white">
                                        {{ __('web.course_player.section_summary', ['count' => $day['items_count']]) }}
                                    </span>
                                </div>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>

        @if ($generalAttachments->isNotEmpty())
            <section class="rounded-[28px] border border-default bg-surface p-4 sm:p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-lg font-semibold text-primary">{{ __('web.course_player.general_attachments') }}</p>
                        <p class="mt-1 text-sm text-muted">{{ __('web.course_player.general_attachments_hint') }}</p>
                    </div>
                    <span class="rounded-full bg-elevated px-3 py-1 text-xs font-semibold text-primary">{{ $generalAttachments->count() }}</span>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    @foreach ($generalAttachments as $item)
                        <div wire:key="{{ $item['key'] }}">
                            <x-course-player-attachment-card :item="$item" />
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-account-shell>
