<div class="bg-white text-[var(--text-strong)] full-bleed">

    {{-- Breadcrumb --}}
    <div class="app-container pt-8 pb-4">
        <nav class="flex items-center gap-2 text-sm" aria-label="breadcrumb">
            <a href="{{ route('cart') }}" class="font-bold text-[var(--text-heading)]">{{ __('web.cart.title') }}</a>
            <span class="text-[var(--text-faded)]">></span>
            <span class="text-[var(--text-faded)]">{{ __('web.checkout.invoice_information') }}</span>
            <span class="text-[var(--text-faded)]">></span>
            <span class="text-[var(--text-faded)]">{{ __('web.checkout.confirm_review') }}</span>
        </nav>
    </div>

    <section class="app-container pb-16">
        <div class="flex flex-col gap-8 lg:grid lg:grid-cols-[minmax(0,1fr)_360px]">

            {{-- Items table (right side in RTL) --}}
            <div>
                {{-- Desktop table header --}}
                <div class="hidden md:grid grid-cols-[1fr_110px_190px_110px_44px] border-b border-gray-200 pb-3 text-sm font-semibold text-[var(--text-strong)]">
                    <span>{{ __('web.cart.product') }}</span>
                    <span class="text-center">{{ __('web.cart.price') }}</span>
                    <span class="text-center">{{ __('web.cart.quantity') }}</span>
                    <span class="text-center">{{ __('web.cart.total') }}</span>
                    <span></span>
                </div>

                @forelse ($displayItems as $item)
                    <div class="py-5 border-b border-gray-100 flex flex-col gap-4 md:grid md:grid-cols-[1fr_110px_190px_110px_44px] md:items-center">

                        {{-- Product --}}
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 h-20 w-20 overflow-hidden rounded-lg bg-gray-100">
                                @if ($item['thumbnailUrl'] ?? $item['imageUrl'] ?? null)
                                    <img
                                        src="{{ $item['thumbnailUrl'] ?? $item['imageUrl'] }}"
                                        alt="{{ $item['title'] }}"
                                        class="h-full w-full object-cover"
                                    >
                                @endif
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-[var(--text-strong)]">{{ $item['title'] }}</p>
                                @if (!empty($item['subtitle']))
                                    <p class="mt-1 text-xs text-[var(--text-faded)]">{{ $item['subtitle'] }}</p>
                                @endif
                            </div>
                        </div>

                        {{-- Price --}}
                        <div class="flex items-center justify-between md:block">
                            <span class="text-xs font-semibold text-[var(--text-hint)] md:hidden">{{ __('web.cart.price') }}</span>
                            <div class="text-sm font-semibold text-center text-[var(--accent-500)]">
                                @if ($item['showDiscount'] ?? false)
                                    <div class="text-xs text-[var(--text-hint)] line-through">{{ $item['originalPriceAmount'] }} {{ $currency }}</div>
                                @endif
                                {{ $item['priceAmount'] }} {{ $currency }}
                            </div>
                        </div>

                        {{-- Quantity --}}
                        <div class="flex items-center justify-between md:justify-center gap-2">
                            <span class="text-xs font-semibold text-[var(--text-hint)] md:hidden">{{ __('web.cart.quantity') }}</span>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="decreaseQuantity({{ $item['id'] }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 text-[var(--text-strong)] hover:bg-gray-50 transition"
                                    aria-label="{{ __('web.cart.decrease_qty') }}"
                                >-</button>
                                <input
                                    type="number"
                                    min="1"
                                    wire:model.live="quantities.{{ $item['id'] }}"
                                    class="h-8 w-12 rounded border border-gray-300 text-center text-sm font-semibold text-[var(--text-strong)] focus:outline-none focus:border-[var(--accent-500)]"
                                >
                                <button
                                    type="button"
                                    wire:click="increaseQuantity({{ $item['id'] }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-300 text-[var(--text-strong)] hover:bg-gray-50 transition"
                                    aria-label="{{ __('web.cart.increase_qty') }}"
                                >+</button>
                            </div>
                        </div>

                        {{-- Total --}}
                        <div class="flex items-center justify-between md:block">
                            <span class="text-xs font-semibold text-[var(--text-hint)] md:hidden">{{ __('web.cart.total') }}</span>
                            <div class="text-sm font-semibold text-center text-[var(--accent-500)]">{{ $item['lineTotal'] }} {{ $currency }}</div>
                        </div>

                        {{-- Remove --}}
                        <div class="flex justify-end md:justify-center">
                            <button
                                type="button"
                                wire:click="removeItem({{ $item['id'] }})"
                                wire:loading.attr="disabled"
                                aria-label="{{ __('web.cart.remove') }}"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-gray-200 text-gray-400 hover:border-red-300 hover:text-red-400 transition"
                            >
                                <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                                    <path d="M1 1l12 12M13 1L1 13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="py-16 text-center text-sm text-[var(--text-faded)]">
                        {{ __('web.cart.empty') }}
                    </div>
                @endforelse

                {{-- Footer actions --}}
                <div class="mt-6 flex items-center justify-between gap-4 flex-wrap">
                    <a
                        href="{{ route('courses.index') }}"
                        class="inline-flex items-center gap-2 rounded border border-gray-300 px-5 py-2.5 text-sm font-medium text-[var(--text-strong)] hover:bg-gray-50 transition"
                    >
                        ← {{ __('web.cart.continue_shopping') }}
                    </a>
                    @auth
                        <button
                            type="button"
                            wire:click="updateCart"
                            class="inline-flex items-center gap-2 rounded bg-gray-700 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800 transition"
                        >
                            {{ __('web.cart.update') }}
                        </button>
                    @endauth
                </div>
            </div>

            {{-- Summary panel (left side in RTL) --}}
            <div class="rounded border border-gray-200 bg-white p-6 self-start">
                <h3 class="text-base font-bold text-[var(--text-heading)]">{{ __('web.cart.order_total_title') }}</h3>

                <div class="mt-4 border-t border-gray-200 pt-4 space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-[var(--text-faded)]">{{ __('web.cart.subtotal') }}</span>
                        <span class="font-semibold text-[var(--text-strong)]">{{ $displaySubtotal + $itemDiscountTotal }} {{ $currency }}</span>
                    </div>
                    @if ($itemDiscountTotal > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[var(--text-faded)]">{{ __('web.cart.discount') }}</span>
                            <span class="font-semibold text-green-600">-{{ $itemDiscountTotal }} {{ $currency }}</span>
                        </div>
                    @endif
                    @if ($couponDiscountTotal > 0)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-[var(--text-faded)]">{{ __('web.cart.coupon_discount') }}</span>
                            <span class="font-semibold text-green-600">-{{ $couponDiscountTotal }} {{ $currency }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between text-sm font-bold border-t border-gray-200 pt-3">
                        <span class="text-[var(--text-strong)]">{{ __('web.cart.total') }}</span>
                        <span class="text-[var(--accent-500)]">{{ $displayTotal }} {{ $currency }}</span>
                    </div>
                </div>

                <div class="mt-5">
                    <a
                        href="{{ route('checkout') }}"
                        class="block w-full rounded bg-[var(--accent-500)] px-4 py-3 text-center text-sm font-bold text-white hover:brightness-95 transition"
                    >
                        {{ __('web.cart.proceed_to_order') }}
                    </a>
                </div>

                {{-- Coupon --}}
                <div class="mt-5 border-t border-gray-200 pt-5">
                    <div class="flex items-center gap-2 mb-3">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" class="text-[var(--text-faded)]" aria-hidden="true">
                            <path d="M9 15L15 9M9.5 9.5h.01M14.5 14.5h.01M20 12a8 8 0 11-16 0 8 8 0 0116 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <span class="text-sm font-semibold text-[var(--text-strong)]">{{ __('web.checkout.coupon') }}</span>
                    </div>

                    <div x-data="{ couponCode: @entangle('couponCode').live }">
                        <input
                            type="text"
                            x-model="couponCode"
                            placeholder="{{ __('web.cart.coupon_placeholder') }}"
                            class="w-full rounded border border-gray-300 px-4 py-2.5 text-sm text-[var(--text-strong)] placeholder:text-gray-400 focus:border-[var(--accent-500)] focus:outline-none"
                        >
                        @error('couponCode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror

                        @if ($appliedCoupon)
                            <div class="mt-2 text-xs text-[var(--text-faded)]">
                                {{ __('web.cart.coupon_applied_label', ['code' => $appliedCoupon]) }}
                            </div>
                            <button
                                type="button"
                                wire:click="removeCoupon"
                                class="mt-3 w-full rounded border border-gray-300 px-4 py-2.5 text-sm font-medium text-[var(--text-strong)] hover:bg-gray-50 transition"
                            >
                                {{ __('web.checkout.remove_coupon') }}
                            </button>
                        @else
                            <button
                                type="button"
                                wire:click="applyCoupon"
                                wire:loading.attr="disabled"
                                :disabled="couponCode.trim().length === 0"
                                class="mt-3 w-full rounded border border-gray-300 px-4 py-2.5 text-sm font-medium text-[var(--text-strong)] hover:bg-gray-50 transition disabled:opacity-50"
                            >
                                {{ __('web.cart.use_coupon') }}
                            </button>
                        @endif

                        @if ($message)
                            <div class="mt-2 text-xs text-[var(--accent-500)]">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Suggested courses --}}
        @if (!empty($suggestedCards))
            <div class="mt-16">
                <h2 class="text-2xl font-bold text-[var(--text-heading)]">{{ __('web.cart.interested_title') }}</h2>
                <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($suggestedCards as $card)
                        <x-course-card :card="$card" />
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</div>
