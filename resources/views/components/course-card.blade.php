@props(['card'])

<article class="relative w-full rounded-3xl bg-white shadow-[30px_0_90px_rgba(113,150,147,0.1)]">
    <div class="relative m-4 overflow-hidden rounded-xl">
        <a href="{{ $card['courseUrl'] }}" class="block">
            <img src="{{ $card['imageUrl'] }}" alt="{{ $card['title'] }}" class="h-[195px] w-full object-cover">
        </a>

        <div class="absolute end-2 top-2 flex w-8 flex-col gap-1.5">
            <button type="button" wire:click="toggleWishlist({{ $card['id'] }})"
                aria-pressed="{{ $card['isWishlisted'] ? 'true' : 'false' }}"
                class="card-action flex h-8 w-8 items-center justify-center rounded-[30px] bg-white shadow-[0_4px_4px_rgba(0,0,0,0.09)] {{ $card['isWishlisted'] ? 'is-active text-[#EF4B4B]' : '' }}">
                <x-icon.heart class="h-4 w-4" />
            </button>
            <button type="button" wire:click="addToCart(@js(\App\Models\Course::class), {{ $card['id'] }})"
                class="card-action flex h-8 w-8 items-center justify-center rounded-[30px] text-[var(--nav-icon)] bg-white shadow-[0_4px_4px_rgba(0,0,0,0.09)] {{ $card['isInCart'] ? 'is-active ' : '' }}">
                <x-icon.bag class="h-4 w-4" />
            </button>
            <button type="button" data-copy-url="{{ $card['courseUrl'] }}"
                data-toast="{{ __('web.course.link_copied') }}"
                class="card-action flex h-8 w-8 items-center justify-center rounded-[30px] bg-white shadow-[0_4px_4px_rgba(0,0,0,0.09)]">
                <x-icon.share class="h-4 w-4" />
            </button>
        </div>

        @if (!empty($card['badge']))
            <img src="{{ asset('img/dicount.gif') }}" alt="{{ $card['badge'] }}"
                class="absolute start-2 top-2 h-10 w-10">
        @endif

        @if (!empty($card['category']))
            <div
                class="absolute start-2 top-[153px] flex items-start gap-2.5 rounded-xl bg-gradient-to-b from-[var(--badge-overlay-start)] to-[var(--badge-overlay-end)] px-4 py-2 text-xs font-bold text-white backdrop-blur-[11px]">
                {{ $card['category'] }}
            </div>
        @endif
    </div>

    <div class="px-4 pb-5">

        <a href="{{ $card['courseUrl'] }}" class="text-xl font-bold py-2 text-black">
            {{ $card['title'] }}
        </a>
        <div class="flex items-start justify-between gap-4">

            <div class="flex flex-row items-start gap-1.5">
                <!-- Discounted / Final Price -->
                <div class="flex items-center  text-[#99745c]">
                    <x-icon.currency class="h-4 w-4" />
                    <p class="text-xl font-bold">{{ $card['priceLabel'] }}</p>
                </div>

                <!-- Original Price (under discounted price) -->
                @if (!empty($card['originalPriceLabel']))
                    <div class="flex items-center gap-0.5 text-s text-[#afafaf]">
                        <span class="flex items-center gap-1 line-through">
                            <x-icon.currency class="h-4 w-4" />
                            <span>{{ $card['originalPriceLabel'] }}</span>
                        </span>
                    </div>
                @endif

            </div>
            <div class="flex items-center gap-1.5" aria-label="{{ __('web.course.reviews') }}">
                @php
                    $rating = (float) ($card['ratingAverage'] ?? 0);
                @endphp
                <div class="flex items-center gap-0.5">
                    @foreach (range(1, 5) as $star)
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                            class="{{ $rating >= $star ? 'text-[var(--rating-star)]' : 'text-[#d1d5db]' }}">
                            <path
                                d="M14.4297 9.61154L12.9597 4.77154C12.6697 3.82154 11.3297 3.82154 11.0497 4.77154L9.56971 9.61154H5.11971C4.14971 9.61154 3.74971 10.8615 4.53971 11.4215L8.17972 14.0215L6.74971 18.6315C6.45971 19.5615 7.53972 20.3115 8.30972 19.7215L11.9997 16.9215L15.6897 19.7315C16.4597 20.3215 17.5397 19.5715 17.2497 18.6415L15.8197 14.0315L19.4597 11.4315C20.2497 10.8615 19.8497 9.62154 18.8797 9.62154H14.4297V9.61154Z"
                                fill="currentColor"></path>
                        </svg>
                    @endforeach
                </div>
                <span class="text-xs font-semibold text-[#4b4c53]">{{ $card['ratingValue'] ?? '0.0' }}</span>
            </div>

        </div>
        {{-- <div class="mt-4 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1 rounded-3xl bg-[#f5f5f6] px-3 py-1.5">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"
                    class="h-5 w-5">
                    <path
                        d="M13.75 3.3335H16.25V16.6668H13.75V3.3335ZM3.75 11.6668H6.25V16.6668H3.75V11.6668ZM8.75 7.50016H11.25V16.6668H8.75V7.50016Z"
                        fill="#4B4C53" />
                </svg>
                <p class="text-xs text-[#4b4c53]">{{ $card['levelLabel'] }}</p>
            </div> --}}

            {{-- @if (!empty($card['enrollmentsLabel']))
                <div class="flex items-center gap-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#374746]">
                        <span class="text-xs text-white">{{ $card['enrollmentsLabel'] }}</span>
                    </div>
                </div>
            @endif --}}
        {{-- </div> --}}


        {{-- <div class="mt-3 flex flex-nowrap items-center gap-1.5">
            @if ($card['lessonsCount'])
                <span class="rounded-3xl bg-[#f6f6f6]/80 px-3 py-1.5 text-xs text-[#4f4f4f]">
                    {{ $card['lessonsCount'] }} {{ __('web.courses.lessons_label') }}
                </span>
            @endif
            @if ($card['durationHours'])
                <span class="rounded-3xl bg-[#f6f6f6]/80 px-3 py-1.5 text-xs text-[#4f4f4f]">
                    {{ $card['durationHours'] }} {{ __('web.course.hours') }}
                </span>
            @endif
        </div> --}}
    </div>
</article>
