@props([
    'card',
    'link',
])

<article
    class="relative w-full cursor-pointer rounded-3xl bg-white shadow-[30px_0_90px_rgba(113,150,147,0.1)]"
    role="link"
    tabindex="0"
    onclick="window.location.href='{{ $link }}'"
    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href='{{ $link }}'; }"
>
    <div class="relative m-4 overflow-hidden rounded-xl bg-[var(--auth-image-placeholder)]">
        <a href="{{ $link }}" class="block">
            <img
                src="{{ $card['imageUrl'] }}"
                alt="{{ $card['title'] }}"
                class="h-[195px] w-full object-cover"
            >
        </a>

        <div class="absolute end-2 top-2 flex w-8 flex-col gap-1.5">
            <button
                type="button"
                wire:click="toggleWishlist({{ $card['id'] }})"
                onclick="event.stopPropagation()"
                aria-pressed="{{ $card['isWishlisted'] ? 'true' : 'false' }}"
                class="card-action flex h-8 w-8 items-center justify-center rounded-[30px] bg-white shadow-[0_4px_4px_rgba(0,0,0,0.09)] {{ $card['isWishlisted'] ? 'is-active text-[#EF4B4B]' : '' }}"
            >
                <x-icon.heart class="h-4 w-4" />
            </button>
            <button
                type="button"
                wire:click="addToCart(@js(\App\Models\Bundle::class), {{ $card['id'] }})"
                onclick="event.stopPropagation()"
                class="card-action flex h-8 w-8 items-center justify-center rounded-[30px] bg-white shadow-[0_4px_4px_rgba(0,0,0,0.09)] {{ $card['isInCart'] ? 'is-active text-[#99745c]' : '' }}"
            >
                <x-icon.bag class="h-4 w-4" />
            </button>
            <button
                type="button"
                data-copy-url="{{ $link }}"
                data-toast="{{ __('web.course.link_copied') }}"
                onclick="event.stopPropagation()"
                class="card-action flex h-8 w-8 items-center justify-center rounded-[30px] bg-white shadow-[0_4px_4px_rgba(0,0,0,0.09)]"
            >
                <x-icon.share class="h-4 w-4" />
            </button>
        </div>

        @if (! empty($card['badge']))
            <img
                src="{{ asset('img/dicount.gif') }}"
                alt="{{ $card['badge'] }}"
                class="absolute start-2 top-2 h-10 w-10"
            >
        @endif

        @if (! empty($card['category']))
            <div class="absolute start-2 top-[153px] flex items-start gap-2.5 rounded-xl bg-gradient-to-b from-[var(--badge-overlay-start)] to-[var(--badge-overlay-end)] px-4 py-2 text-xs font-bold text-white backdrop-blur-[11px]">
                {{ $card['category'] }}
            </div>
        @endif
    </div>

    <div class="px-4 pb-5">
        <div class="flex items-start justify-between gap-4">
            <div class="flex min-w-0 flex-1 items-start gap-4">
                <a href="{{ $link }}" class="text-xl font-bold text-black">
                    {{ $card['title'] }}
                </a>
            </div>
            <div class="flex flex-col items-start gap-1.5">
                <!-- Discounted / Final Price -->
                <div class="flex items-center  text-[#99745c]">
                    <x-icon.currency class="h-4 w-4" />
                    <p class="text-xl font-bold">{{ $card['priceLabel'] }}</p>
                </div>

                <!-- Original Price (under discounted price) -->
                @if (!empty($card['originalPriceLabel']))
                    <div class="flex items-center gap-0.5 text-xs text-[#afafaf]">
                        <span class="flex items-center gap-1 line-through">
                            <x-icon.currency class="h-4 w-4" />
                            <span>{{ $card['originalPriceLabel'] }}</span>
                        </span>
                    </div>
                @endif

            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <span class="rounded-3xl bg-[#f6f6f6]/80 px-3 py-1.5 text-xs text-[#4f4f4f]">
                {{ __('web.bundles.course_count', ['count' => $card['coursesCount']]) }}
            </span>
        </div>
    </div>
</article>
