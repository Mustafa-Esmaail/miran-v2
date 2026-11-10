@props(['groups', 'heading' => null])

@if (!empty($groups) && $groups->count() > 0)
    <div class="course-profile-shell mx-auto w-full max-w-2xl rounded-3xl bg-surface px-6 py-6 sm:px-10 lg:px-[72px]"
        x-data="{ cvModalUrl: null, cvModalName: null }">
        @if ($heading)
            <h2 class="text-xl font-bold text-[#100f14] mb-4 text-center">{{ $heading }}</h2>
        @endif
        <div class="overflow-hidden overflow-x-auto rounded-2xl border border-gray-200">
            <table class="w-full text-center text-sm">
                <thead class="bg-[#374746]">
                    <tr>
                        <th class="px-4 py-3 text-center font-semibold text-white">
                            {{ __('web.course_profile.group_date') }}</th>
                        <th class="px-4 py-3 text-center font-semibold text-white">
                            {{ __('web.course_profile.group_instructor') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $group)
                        <tr class="odd:bg-white even:bg-gray-50 hover:bg-[#f4efe9] transition-colors">
                            <td class="px-4 py-3">{{ optional($group->start_at)->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col items-center gap-1">
                                    @foreach ($group->instructors as $instructor)
                                        <div class="flex items-center justify-center gap-2">
                                            <span>{{ $instructor->name }}</span>
                                            @if ($instructor->cv_image_path)
                                                <button type="button"
                                                    @click="cvModalUrl = {{ \Illuminate\Support\Js::from($instructor->cv_image_url) }}; cvModalName = {{ \Illuminate\Support\Js::from($instructor->name) }}"
                                                    class="rounded-full bg-[#374746] px-3 py-1 text-xs font-medium text-white transition-colors hover:bg-[#2a3736]">
                                                    {{ __('web.course_profile.view_cv') }}
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div x-cloak x-show="cvModalUrl" style="z-index: 9999; background:#00000075"
            class="fixed inset-0 flex items-center justify-center px-4 py-6">
            <div class="absolute inset-0" @click="cvModalUrl = null"></div>

            <div class="relative z-[999999] w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-3">
                    <h3 class="text-base font-semibold text-[#100f14]" x-text="cvModalName || '{{ __('web.course_profile.cv_modal_title') }}'"></h3>
                    <button type="button" @click="cvModalUrl = null"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-[#100f14] hover:bg-gray-100"
                        aria-label="Close">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                            <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        </svg>
                    </button>
                </div>
                <div class="max-h-[80vh] overflow-y-auto p-4">
                    <img :src="cvModalUrl" :alt="cvModalName" class="w-full rounded-xl object-contain">
                </div>
            </div>
        </div>
    </div>
@endif