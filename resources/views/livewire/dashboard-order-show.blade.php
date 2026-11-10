<x-account-shell active="orders" :title="__('web.orders.show_title', ['number' => $order['order_no']])">
    @if ($gtmPurchase)
        <x-gtm-event event="purchase" :data="$gtmPurchase" />
    @endif
    @if ($gtmSignUp)
        <x-gtm-event event="sign_up" :data="$gtmSignUp" />
    @endif

    @if (session('order_success'))
        <div x-data="{ open: true }">
            <div class="fixed inset-0 z-40 bg-[#0d0d12]/40" x-show="open" x-cloak></div>
            <div
                class="fixed left-1/2 top-1/2 z-50 w-[90%] max-w-[520px] -translate-x-1/2 -translate-y-1/2"
                x-show="open"
                x-cloak
            >
                <div class="flex flex-col justify-start items-center w-full relative gap-5 px-6 py-8 rounded-3xl bg-white">
                <img src="{{ asset('img/success.gif') }}" alt="{{ __('web.orders.success_alt') }}" class="h-20 w-20" />
                <div class="flex flex-col justify-start items-start self-stretch gap-5">
                    <p class="w-full text-2xl font-bold text-center text-[#242528]">
                        {{ __('web.orders.thanks_title') }}
                    </p>
                    <p class="w-full text-base text-center text-[#100f14]">
                        <span class="text-base text-center text-[#100f14]">
                            {{ __('web.orders.thanks_line_1') }}
                        </span><br />
                        <span class="text-base text-center text-[#100f14]">
                            {{ __('web.orders.thanks_line_2') }}
                        </span>
                    </p>
                </div>
                <div class="flex flex-col justify-start items-center self-stretch flex-grow-0 flex-shrink-0 gap-4">
                    <button
                        type="button"
                        class="flex justify-center items-center self-stretch flex-grow-0 flex-shrink-0 h-12 gap-2.5 px-3 py-2.5 rounded-[100px] bg-[#99745c]"
                        @click="open = false"
                    >
                        <div class="flex justify-center items-center flex-grow-0 flex-shrink-0 relative gap-1.5">
                            <p class="flex-grow-0 flex-shrink-0 text-sm font-semibold text-left text-white">
                                {{ __('web.orders.view_details') }}
                            </p>
                        </div>
                    </button>
                    <a
                        href="{{ route('home') }}"
                        class="flex justify-center items-center self-stretch flex-grow-0 flex-shrink-0 h-[42px] gap-2.5 px-3 py-2.5 rounded-xl"
                    >
                        <div class="flex justify-center items-center flex-grow-0 flex-shrink-0 relative gap-1.5">
                            <p class="flex-grow-0 flex-shrink-0 text-sm font-semibold text-left text-[#242528]">
                                {{ __('web.common.back_to_home') }}
                            </p>
                        </div>
                    </a>
                </div>
                </div>
            </div>
        </div>
    @endif
    <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr]">
        <div class="rounded-2xl border border-[#ebebeb] bg-white p-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs text-[#818181]">{{ __('web.orders.order_number', ['number' => $order['order_no']]) }}</p>
                    <p class="text-lg font-semibold text-[#100f14]">#{{ $order['order_no'] }}</p>
                </div>
                <div class="text-sm font-semibold text-[#99745c]">
                    {{ $order['total_amount'] }} {{ $order['currency'] }}
                </div>
            </div>
            <div class="mt-4 grid gap-3 text-sm text-[#100f14]">
                <div class="flex items-center justify-between">
                    <span class="text-[#818181]">{{ __('web.orders.status') }}</span>
                    <span class="font-medium">{{ $order['status'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[#818181]">{{ __('web.orders.payment_method') }}</span>
                    <span class="font-medium">{{ $order['payment_method'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[#818181]">{{ __('web.orders.payment_status') }}</span>
                    <span class="font-medium">{{ $order['payment_status'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[#818181]">{{ __('web.orders.total') }}</span>
                    <span class="font-medium">{{ $order['total_amount'] }} {{ $order['currency'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[#818181]">{{ __('web.orders.subtotal') }}</span>
                    <span class="font-medium">{{ $order['subtotal_amount'] }} {{ $order['currency'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[#818181]">{{ __('web.orders.discount') }}</span>
                    <span class="font-medium">{{ $order['discount_amount'] }} {{ $order['currency'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-[#818181]">{{ __('web.orders.date') }}</span>
                    <span class="font-medium">{{ $order['created_at'] }}</span>
                </div>
                @if ($order['notes'])
                    <div class="rounded-lg bg-[#f8fafb] px-3 py-2 text-xs text-[#4f4f4f]">
                        {{ $order['notes'] }}
                    </div>
                @endif
                @if ($order['invoice_url'])
                    <a
                        href="{{ $order['invoice_url'] }}"
                        target="_blank"
                        rel="noopener"
                        class="mt-1 inline-flex items-center justify-center gap-2 rounded-xl border border-[#ebebeb] px-3 py-2 text-sm font-semibold text-[#99745c] hover:bg-[#f8fafb]"
                    >
                        {{ __('web.orders.download_invoice') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-[#ebebeb] bg-white p-6">
            <p class="text-sm font-semibold text-[#30373d]">{{ __('web.orders.order_items') }}</p>
            <div class="mt-4 space-y-3">
                @foreach ($items as $item)
                    <div class="rounded-xl border border-[#ebebeb] bg-white px-4 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="h-[60px] w-[60px] overflow-hidden rounded-[10px] bg-[#f5f5f6]">
                                    @if ($item['thumbnail_url'])
                                        <img src="{{ $item['thumbnail_url'] }}" alt="{{ $item['title'] }}" class="h-full w-full object-cover">
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-[#100f14]">{{ $item['title'] }}</p>
                                    <p class="text-xs text-[#818181]">{{ $item['subtitle'] }}</p>
                                    <p class="mt-1 text-xs text-[#818181]">{{ __('web.orders.type_label', ['type' => $item['type']]) }}</p>
                                    @if ($item['course_slug'] && $item['group_id'])
                                        <a class="mt-2 inline-flex text-xs font-semibold text-[#99745c]" href="{{ route('dashboard.course-player', [$item['course_slug'], $item['group_id']]) }}">
                                            {{ __('web.orders.course_link') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right text-sm text-[#100f14]">
                                <p class="font-semibold">{{ $item['total_amount'] }} {{ $order['currency'] }}</p>
                                <p class="text-xs text-[#818181]">{{ $item['quantity'] }} x {{ $item['price_amount'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-6">
        <livewire:dashboard.order-seats :order-id="$order['id']" />
    </div>
</x-account-shell>
