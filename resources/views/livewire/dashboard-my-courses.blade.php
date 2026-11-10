<x-account-shell active="courses" :title="__('web.my_courses.title')">
    <div class="flex flex-col gap-4">
        <div class="overflow-hidden rounded-2xl border border-[#ebebeb] bg-white">
            <div class="bg-[#99745c]/10 px-6 py-4">
                <div class="grid grid-cols-[72px_1fr] items-center gap-4 text-sm font-medium text-[#100f14] md:grid-cols-[72px_1fr_160px_140px]">
                    <span>{{ __('web.my_courses.image') }}</span>
                    <span>{{ __('web.my_courses.product_name') }}</span>
                    <span class="hidden md:block">{{ __('web.my_courses.enrolment_date') }}</span>
                    <span class="hidden md:block">{{ __('web.my_courses.course_link') }}</span>
                </div>
            </div>
            <div class="divide-y divide-[#a8a7ae]/20">
                @forelse ($enrollments as $enrollment)
                    <div class="grid grid-cols-[72px_1fr] items-start gap-4 px-6 py-5 text-sm text-[#100f14] md:grid-cols-[72px_1fr_160px_140px]">
                        <div class="h-[60px] w-[60px] overflow-hidden rounded-[10px] bg-[#f5f5f6]">
                            @if ($enrollment['thumbnail_url'])
                                <img src="{{ $enrollment['thumbnail_url'] }}" alt="{{ $enrollment['course_title'] }}"
                                    class="h-full w-full object-cover">
                            @endif
                        </div>

                        {{-- Course title column — on mobile also shows the hidden columns stacked --}}
                        <div class="flex flex-col gap-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-base font-medium text-[#100f14]">{{ $enrollment['course_title'] }}</p>
                                @if ($enrollment['is_invited'])
                                    <span class="rounded-full bg-[#99745c]/10 px-2 py-0.5 text-[10px] font-semibold text-[#99745c]">
                                        {{ __('web.checkout.invited_badge') }}
                                    </span>
                                @endif
                            </div>
                            @if ($enrollment['group_title'])
                                <p class="text-xs text-[#818181]">{{ $enrollment['group_title'] }}</p>
                            @endif

                            {{-- Extra info visible only on mobile --}}
                            <div class="mt-1 flex flex-col gap-0.5 md:hidden">
                                <p class="text-xs text-[#818181]">{{ $enrollment['enrolled_at'] ?? '--/--/----' }}</p>
                                @if ($enrollment['course_slug'] && $enrollment['group_id'])
                                    <a class="text-xs font-semibold text-[#99745c]"
                                        href="{{ route('dashboard.course-player', [$enrollment['course_slug'], $enrollment['group_id']]) }}">
                                        {{ __('web.my_courses.course_link') }}
                                    </a>
                                @else
                                    <span class="text-xs font-semibold text-[#99745c]">{{ __('web.my_courses.course_link') }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Desktop-only columns --}}
                        <div class="hidden text-base text-[#100f14] md:block">{{ $enrollment['enrolled_at'] ?? '--/--/----' }}</div>
                        @if ($enrollment['course_slug'] && $enrollment['group_id'])
                            <a class="hidden text-base font-semibold text-[#99745c] md:block"
                                href="{{ route('dashboard.course-player', [$enrollment['course_slug'], $enrollment['group_id']]) }}">
                                {{ __('web.my_courses.course_link') }}
                            </a>
                        @else
                            <span class="hidden text-base font-semibold text-[#99745c] md:block">{{ __('web.my_courses.course_link') }}</span>
                        @endif
                    </div>
                @empty
                    <div class="px-6 pb-6 text-sm text-[#818181]">{{ __('web.my_courses.empty') }}</div>
                @endforelse
            </div>
        </div>
    </div>
</x-account-shell>
