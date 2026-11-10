<div class="app-container py-12">
    <div class="mx-auto max-w-md rounded-3xl border border-[#e4dccf] bg-white p-8 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[#99745c]/10 text-[#99745c]">
            <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75" />
                <path d="M12 7.5V12l3 2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>

        <p class="text-base font-semibold text-[#100f14]">{{ __('web.checkout.redirecting_to_payment_title') }}</p>
        <p class="mt-2 text-sm text-[#6b6b6b]">
            {{ __('web.checkout.redirecting_to_payment_body', ['order_no' => $order_no]) }}
        </p>

        @if ($redirectUrl)
            <a href="{{ $redirectUrl }}"
                class="mt-6 inline-flex items-center justify-center rounded-full bg-[#99745c] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#7d5f49]">
                {{ __('web.checkout.redirecting_to_payment_action') }}
            </a>
        @endif
    </div>
</div>
