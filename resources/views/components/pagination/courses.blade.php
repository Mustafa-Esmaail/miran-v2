@if ($paginator->hasPages())
    <nav role="navigation" class="flex flex-wrap items-center justify-center gap-6">
        @if ($paginator->onFirstPage())
            <span
                class="inline-flex items-center gap-2 rounded-xl border border-[#f0f2f5] bg-[#f5f7fa] px-4 py-2.5 text-sm font-semibold text-[#71757a]">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
                    <path stroke="#71757A" d="M5.50016 11H16.9585M16.9585 11L11.4585 5.5M16.9585 11L11.4585 16.5"
                        stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
                Prev
            </span>
        @else
            <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-xl border border-[#f0f2f5] bg-[#f5f7fa] px-4 py-2.5 text-sm font-semibold text-[#71757a]">

                Prev
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
                    <path stroke="#71757A" d="M5.50016 11H16.9585M16.9585 11L11.4585 5.5M16.9585 11L11.4585 16.5"
                        stroke="white" stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
        @endif

        <div class="flex flex-wrap items-center gap-2">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-3 py-2 text-sm text-[#71757a]">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span
                                class="inline-flex items-center justify-center rounded-xl bg-[#99745c] px-4 py-2.5 text-sm font-bold text-white">
                                {{ $page }}
                            </span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }})"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center rounded-xl border border-white bg-white px-4 py-2.5 text-sm font-semibold text-[#71757a]">
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-xl bg-[#99745c] px-4 py-2.5 text-sm font-semibold text-white">

                Next

                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
                    <path d="M16.9583 11H5.5M5.5 11L11 5.5M5.5 11L11 16.5" stroke="#fff" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </button>
        @else
            <span
                class="inline-flex items-center gap-2 rounded-xl bg-[#99745c] px-4 py-2.5 text-sm font-semibold text-white opacity-60">

                Next
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true">
                    <path d="M16.9583 11H5.5M5.5 11L11 5.5M5.5 11L11 16.5" stroke="#fff" stroke-width="1.5"
                        stroke-linecap="round" stroke-linejoin="round"></path>
                </svg>
            </span>
        @endif
    </nav>
@endif
