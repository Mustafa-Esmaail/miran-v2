@props([
    'item',
    'currency' => 'SAR',
    'showDiscount' => false,
    'showRemove' => false,
    'wireClick' => null,
])

<div class="flex flex-col gap-4 rounded-xl border border-default bg-surface p-4 sm:flex-row">
    <div class="h-20 w-full overflow-hidden rounded-lg bg-muted sm:h-24 sm:w-32">

        @if ($item['thumbnailUrl'])
            <img src="{{ $item['thumbnailUrl'] ?? $item['imageUrl'] }}" alt="{{ $item['courseTitle'] }}" class="h-full w-full object-cover">
        @endif
    </div>
    <div class="flex-1">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-sm font-semibold text-primary">{{ $item['title'] }}</div>
                @if (! empty($item['subtitle']))
                    <div class="mt-1 text-xs text-muted">{{ $item['subtitle'] }}</div>
                @endif
            </div>
            @if ($showRemove && $wireClick)
                <button
                    class="text-xs text-alert"
                    wire:click="{{ $wireClick }}"
                    wire:loading.class="is-loading"
                    wire:loading.attr="disabled"
                >
                    {{ __('web.cart.remove') }}
                </button>
            @endif
        </div>
        <div class="mt-3 flex items-center justify-between text-sm">
            <span class="text-muted">{{ __('web.cart.total') }}</span>
            <div class="text-end">
                @if ($showDiscount)
                    <div class="text-xs text-muted line-through">{{ $item['lineTotal'] }} {{ $currency }}</div>
                @endif
                <div class="font-semibold text-primary">{{ $item['discountedTotal'] }} {{ $currency }}</div>
            </div>
        </div>
    </div>
</div>
