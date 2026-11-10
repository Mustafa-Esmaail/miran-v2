@if ($gtmPurchase)
    <x-gtm-event event="purchase" :data="$gtmPurchase" />
@endif
@if ($gtmSignUp)
    <x-gtm-event event="sign_up" :data="$gtmSignUp" />
@endif
<div class="app-container py-12">
    <div class="mx-auto max-w-md rounded-3xl border border-[#e4dccf] bg-white p-8 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100 text-green-600">
            <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" aria-hidden="true">
                <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>

        <p class="text-base font-semibold text-[#100f14]">{{ __('web.checkout.payment_success_title') }}</p>
        <p class="mt-2 text-sm text-[#6b6b6b]">
            {{ __('web.checkout.payment_success_body', ['order_no' => $order_no]) }}
        </p>

        <a href="{{ route('dashboard.orders.show', ['order_no' => $order_no]) }}"
            class="mt-6 inline-flex items-center justify-center rounded-full bg-[#99745c] px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-[#7d5f49]">
            {{ __('web.checkout.payment_success_action') }}
        </a>
    </div>

    @if ($invoice)
        <div class="mx-auto mt-6 max-w-md rounded-3xl border border-[#e4dccf] bg-white p-8 shadow-sm">
            <p class="text-base font-semibold text-[#100f14]">{{ __('web.orders.invoice_title') }}</p>

            <div class="mt-4 grid gap-2 text-sm text-[#100f14]">
                @if ($invoice['phone'])
                    <div class="flex items-center justify-between">
                        <span class="text-[#818181]">{{ __('web.checkout.phone') }}</span>
                        <span class="font-medium">{{ $invoice['phone'] }}</span>
                    </div>
                @endif
                @if ($invoice['email'])
                    <div class="flex items-center justify-between">
                        <span class="text-[#818181]">{{ __('web.checkout.email') }}</span>
                        <span class="font-medium">{{ $invoice['email'] }}</span>
                    </div>
                @endif
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm text-[#100f14]">
                    <thead>
                        <tr class="border-b border-[#e4dccf] text-[#818181]">
                            <th class="py-2 text-start font-medium">{{ __('web.orders.item') }}</th>
                            <th class="py-2 text-center font-medium">{{ __('web.orders.qty') }}</th>
                            <th class="py-2 text-end font-medium">{{ __('web.orders.price') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoiceItems as $item)
                            <tr class="border-b border-[#f1ece3] last:border-0">
                                <td class="py-2 text-start">{{ $item['title'] }}</td>
                                <td class="py-2 text-center">{{ $item['quantity'] }}</td>
                                <td class="py-2 text-end">{{ $item['total_amount'] }} {{ $invoice['currency'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between border-t border-[#e4dccf] pt-4 text-sm font-semibold text-[#100f14]">
                <span>{{ __('web.orders.total') }}</span>
                <span>{{ $invoice['total_amount'] }} {{ $invoice['currency'] }}</span>
            </div>
        </div>
    @endif
</div>
