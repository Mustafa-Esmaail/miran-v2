<x-account-shell active="downloads" :title="__('web.downloads.title')">
    <div class="flex flex-col gap-4">
        <div class="overflow-hidden rounded-2xl border border-[#ebebeb] bg-white">
            <div class="bg-[#99745c]/10 px-6 py-4">
                <div
                    class="grid items-center gap-4 text-sm font-medium text-[#100f14] md:grid-cols-[1.2fr_1fr_160px_140px_120px]">
                    <span>{{ __('web.downloads.file') }}</span>
                    <span>{{ __('web.downloads.course') }}</span>
                    <span>{{ __('web.downloads.type') }}</span>
                    <span>{{ __('web.downloads.date') }}</span>
                    <span></span>
                </div>
            </div>
            <div class="divide-y divide-[#a8a7ae]/20">
                @forelse ($downloads as $download)
                    <div
                        class="grid items-center gap-4 px-6 py-5 text-sm text-[#100f14] ] overflow-x-auto md:grid-cols-[1.2fr_1fr_160px_140px_120px]">
                        <div class="text-base font-medium text-[#100f14]">{{ $download['title'] }}</div>
                        <div class="text-sm text-[#818181]">{{ $download['course_title'] }}</div>
                        <div class="text-sm text-[#100f14]">{{ $download['type'] }}</div>
                        <div class="text-sm text-[#100f14]">{{ $download['created_at'] ?? '--/--/----' }}</div>
                        <a class="text-base font-semibold text-[#99745c]" href="{{ $download['url'] }}" target="_blank"
                            rel="noopener noreferrer">
                            {{ __('web.downloads.download') }}
                        </a>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center gap-4 px-6 py-12 text-center text-[#30373d]">
                        <p class="text-base font-medium">{{ __('web.downloads.empty') }}</p>
                        <svg width="120" height="120" viewBox="0 0 250 250" fill="none"
                            xmlns="http://www.w3.org/2000/svg" class="h-[120px] w-[120px]" aria-hidden="true">
                            <g opacity="0.2">
                                <path d="M83.332 127.083H156.249" stroke="#818181" stroke-width="10"
                                    stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M83.332 168.75H128.957" stroke="#818181" stroke-width="10"
                                    stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path
                                    d="M104.165 62.5H145.832C166.665 62.5 166.665 52.0833 166.665 41.6666C166.665 20.8333 156.249 20.8333 145.832 20.8333H104.165C93.7487 20.8333 83.332 20.8333 83.332 41.6666C83.332 62.5 93.7487 62.5 104.165 62.5Z"
                                    stroke="#818181" stroke-width="10" stroke-miterlimit="10" stroke-linecap="round"
                                    stroke-linejoin="round"></path>
                                <path
                                    d="M166.667 41.875C201.354 43.75 218.75 56.5625 218.75 104.167V166.667C218.75 208.333 208.333 229.167 156.25 229.167H93.75C41.6667 229.167 31.25 208.333 31.25 166.667V104.167C31.25 56.6667 48.6458 43.75 83.3333 41.875"
                                    stroke="#818181" stroke-width="10" stroke-miterlimit="10" stroke-linecap="round"
                                    stroke-linejoin="round"></path>
                            </g>
                        </svg>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-account-shell>
