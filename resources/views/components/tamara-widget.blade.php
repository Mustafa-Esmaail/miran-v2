@props([
    'amount' => null,
    'currency' => 'SAR',
    'publicKey' => null,
])

@php
    $floatAmount = $amount !== null ? (float) $amount : 0;
    $lang = app()->getLocale() === 'ar' ? 'ar' : 'en';
@endphp

@if (!empty($publicKey) && $floatAmount > 0)
    <div class="px-4 py-3 overflow-hidden rounded-xl text-primary">
        <tamara-widget type="tamara-summary" amount="{{ number_format($floatAmount, 2, '.', '') }}"
            currency="{{ $currency }}" country-code="SA" public-key="{{ $publicKey }}" inline-type="2"
            lang="{{ $lang }}"></tamara-widget>
    </div>

    @once
        <script src="https://cdn.tamara.co/widget-v2/tamara-widget.js" async></script>
        <script>
            document.addEventListener('livewire:updated', function() {
                if (window.TamaraWidgetV2) {
                    window.TamaraWidgetV2.refresh();
                }
            });
        </script>
    @endonce
@endif
