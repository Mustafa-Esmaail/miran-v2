@props([
    'title',
    'subtotal' => 0,
    'total' => 0,
    'currency' => 'SAR',
    'itemDiscountTotal' => 0,
    'couponDiscountTotal' => 0,
    'appliedCoupon' => null,
])

<div class="relative overflow-hidden rounded-[20px] border border-[var(--panel-border)] bg-white p-5 shadow-[0_55px_90px_rgba(0,0,0,0.03)]">
    <p class="text-base font-bold text-left text-[var(--text-strong)]">{{ $title }}</p>
    <div class="mt-2 h-px w-full bg-[var(--panel-border)]"></div>
    <div class="mt-3 flex items-center justify-between">
        <span class="text-base font-bold text-[var(--accent-500)]">
            {{ $subtotal }} {{ $currency }}
        </span>
    </div>

    @if ($itemDiscountTotal > 0)
        <div class="mt-4 flex items-center justify-between text-base text-[var(--text-strong)]">
            <span>{{ __('web.cart.discount') }}</span>
            <span class="font-medium text-[var(--accent-500)]">-{{ $itemDiscountTotal }} {{ $currency }}</span>
        </div>
    @endif

    @if ($appliedCoupon && $couponDiscountTotal > 0)
        <div class="mt-4 flex items-center justify-between text-base text-[var(--text-strong)]">
            <span>{{ __('web.cart.coupon_discount') }} ({{ $appliedCoupon }})</span>
            <span class="font-medium text-[var(--accent-500)]">-{{ $couponDiscountTotal }} {{ $currency }}</span>
        </div>
    @endif

    {{ $slot }}

    <div class="mt-4 h-px w-full bg-[var(--panel-border)]"></div>
    <div class="mt-4 flex items-center justify-between text-base font-bold text-[var(--text-strong)]">
        <span>{{ __('web.cart.grand_total') }}</span>
        <span class="text-[var(--accent-500)]">{{ $total }} {{ $currency }}</span>
    </div>

    @isset($footer)
        <div class="mt-6">
            {{ $footer }}
        </div>
    @endisset
</div>
