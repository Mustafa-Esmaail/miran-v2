<x-account-shell active="orders" :title="__('web.orders.title')">
    <div class="flex flex-col gap-4">
        <div class="overflow-hidden rounded-2xl border border-[#ebebeb] bg-white">
            <div class="bg-[#99745c]/10 px-6 py-4">
                <div
                    class="grid items-center gap-4 text-sm font-medium text-[#100f14] md:grid-cols-[160px_140px_220px_200px_120px_120px]">
                    <span>{{ __('web.orders.order_number', ['number' => '#']) }}</span>
                    <span>{{ __('web.orders.status') }}</span>
                    <span>{{ __('web.orders.items_label') }}</span>
                    <span>{{ __('web.orders.payment_label') }}</span>
                    <span>{{ __('web.orders.total') }}</span>
                    <span></span>
                </div>
            </div>
            <div class="divide-y divide-[#a8a7ae]/20">
                @forelse ($orders as $order)
                    @php
                        $itemParts = [];
                        if ($order['course_count']) {
                            $itemParts[] = __('web.orders.items_courses', ['count' => $order['course_count']]);
                        }
                        if ($order['bundle_count']) {
                            $itemParts[] = __('web.orders.items_bundles', ['count' => $order['bundle_count']]);
                        }
                    @endphp
                    <div
                        class="grid items-center gap-4 px-6 py-5 text-sm text-[#100f14] ] overflow-x-auto md:grid-cols-[160px_140px_220px_200px_120px_120px]">
                        <div class="text-base font-semibold text-[#100f14]">#{{ $order['order_no'] }}</div>
                        <div class="text-sm text-[#818181]">{{ $order['status'] }}</div>
                        <div class="text-sm text-[#100f14]">
                            {{ $itemParts ? implode(' ' . __('web.common.separator') . ' ', $itemParts) : __('web.orders.no_items') }}
                            @if ($order['assigned_seats'] || $order['available_seats'])
                                <div class="mt-1 text-xs text-[#818181]">
                                    {{ __('web.checkout.seat_summary', ['assigned' => $order['assigned_seats'], 'available' => $order['available_seats']]) }}
                                </div>
                            @endif
                        </div>
                        <div class="text-sm text-[#100f14]">
                            <div class="font-medium">{{ $order['payment_method'] }}</div>
                            <div class="text-xs text-[#818181]">{{ $order['payment_status'] }}</div>
                        </div>
                        <div class="text-base font-semibold text-[#99745c]">
                            {{ $order['total_amount'] }} {{ $order['currency'] }}
                        </div>
                        <a class="text-base font-semibold text-[#99745c]"
                            href="{{ route('dashboard.orders.show', $order['order_no']) }}">
                            {{ __('web.orders.view') }}
                        </a>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center gap-4 px-6 py-12 text-center text-[#30373d]">
                        <p class="text-base font-medium">{{ __('web.orders.empty') }}</p>
                        <svg width="120" height="120" viewBox="0 0 250 250" fill="none"
                            xmlns="http://www.w3.org/2000/svg" class="h-[120px] w-[120px]" aria-hidden="true">
                            <g opacity="0.2">
                                <path
                                    d="M247.725 85.73C247.713 85.7148 247.707 85.6986 247.694 85.6829L220.54 51.8759C220.235 51.4966 219.89 51.1698 219.523 50.8776C218.355 49.4914 216.626 48.5916 214.672 48.5916H35.3285C33.3725 48.5916 31.647 49.4903 30.4777 50.8776C30.1108 51.1698 29.7656 51.4934 29.4577 51.8759L2.30718 85.6829C2.29474 85.6986 2.28879 85.7148 2.27634 85.73C0.895495 86.8993 0 88.6226 0 90.5721V195.039C0 198.556 2.85152 201.408 6.36857 201.408H243.632C247.149 201.408 250.001 198.556 250.001 195.039V90.5721C250.001 88.6226 249.105 86.8993 247.725 85.73ZM221.042 72.8381L230.171 84.2036H221.042V72.8381ZM208.304 61.3292V84.203H160.616C159.255 84.203 157.934 84.6386 156.839 85.4437L125.004 108.872L93.1612 85.4405C92.0666 84.6386 90.7448 84.203 89.3855 84.203H41.6965V61.3292H208.304ZM28.9605 84.2036H19.8307L28.9605 72.8381V84.2036ZM237.264 188.671H12.7371V96.9407H87.2964L121.228 121.911C123.474 123.559 126.534 123.562 128.779 121.908L162.704 96.9402H237.264V188.671Z"
                                    fill="#818181"></path>
                            </g>
                        </svg>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-account-shell>
