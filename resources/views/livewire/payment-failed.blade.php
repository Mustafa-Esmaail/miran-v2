<div class="app-container py-12">
    <div class="mx-auto max-w-md rounded-3xl border border-[#e4dccf] bg-white p-8 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
            <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>

        <p class="text-base font-semibold text-[#100f14]">{{ __('web.checkout.payment_failed_title') }}</p>
        <p class="mt-2 text-sm text-[#6b6b6b]">
            {{ __('web.checkout.payment_failed_body', ['order_no' => $order_no]) }}
        </p>

        <a href="{{ route('dashboard.orders.show', ['order_no' => $order_no]) }}"
            class="mt-6 inline-flex items-center justify-center rounded-full bg-[#99745c] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#7d5f49]">
            {{ __('web.checkout.payment_failed_action') }}
        </a>
    </div>
</div>
