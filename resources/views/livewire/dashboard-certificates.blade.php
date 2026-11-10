<x-account-shell active="certificates" :title="__('web.certificates.title')">
    <div class="flex flex-col gap-4">
        <div class="overflow-hidden rounded-2xl border border-[#ebebeb] bg-white">
            <div class="bg-[#99745c]/10 px-6 py-4">
                <div class="grid items-center gap-4 text-sm font-medium text-[#100f14] md:grid-cols-[1.5fr_1fr_180px_120px]">
                    <span>{{ __('web.certificates.course') }}</span>
                    <span>{{ __('web.certificates.group') }}</span>
                    <span>{{ __('web.certificates.form_status') }}</span>
                    <span></span>
                </div>
            </div>
            <div class="divide-y divide-[#a8a7ae]/20">
                @forelse ($rows as $row)
                    <div class="grid items-center gap-4 px-6 py-5 text-sm text-[#100f14] md:grid-cols-[1.5fr_1fr_180px_120px]">
                        <div class="text-base font-medium text-[#100f14]">{{ $row['course_title'] }}</div>
                        <div class="text-sm text-[#818181]">{{ $row['group_title'] }}</div>
                        <div>
                            @if ($row['submitted'])
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-[#e7f4ed] px-3 py-1 text-xs font-semibold text-[#1f7a4c]">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M12 2C6.49 2 2 6.49 2 12s4.49 10 10 10 10-4.49 10-10S17.51 2 12 2Zm4.78 7.7-5.67 5.67a.75.75 0 0 1-1.06 0l-2.83-2.83a.754.754 0 0 1 0-1.06c.29-.29.77-.29 1.06 0l2.3 2.3 5.14-5.14c.29-.29.77-.29 1.06 0 .29.29.29.77 0 1.06Z"/>
                                    </svg>
                                    {{ __('web.certificates.form_submitted') }}
                                    @if ($row['submitted_at'])
                                        <span class="font-normal text-[#3a8a60]">{{ $row['submitted_at'] }}</span>
                                    @endif
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-yellow-50 px-3 py-1 text-xs font-semibold text-yellow-700">
                                    {{ __('web.certificates.form_pending') }}
                                </span>
                            @endif
                        </div>
                        <div>
                            <a href="{{ $row['apply_url'] }}"
                               class="inline-flex items-center rounded-xl bg-[#99745c] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">
                                {{ $row['submitted'] ? __('web.certificates.form_edit') : __('web.certificates.form_apply') }}
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center gap-4 px-6 py-12 text-center text-[#30373d]">
                        <p class="text-base font-medium">{{ __('web.certificates.empty') }}</p>
                        <svg width="120" height="120" viewBox="0 0 250 250" fill="none"
                            xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <g opacity="0.2">
                                <path d="M125 160C152.614 160 175 137.614 175 110C175 82.3858 152.614 60 125 60C97.3858 60 75 82.3858 75 110C75 137.614 97.3858 160 125 160Z"
                                    stroke="#818181" stroke-width="10" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M93.75 152.083L83.3333 229.167L125 208.333L166.667 229.167L156.25 152.083"
                                    stroke="#818181" stroke-width="10" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M125 135C138.807 135 150 123.807 150 110C150 96.1929 138.807 85 125 85C111.193 85 100 96.1929 100 110C100 123.807 111.193 135 125 135Z"
                                    stroke="#818181" stroke-width="10" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                            </g>
                        </svg>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-account-shell>
