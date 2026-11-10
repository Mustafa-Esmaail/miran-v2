<x-mail::message>
@if (filled($siteLogoUrl))
<div style="margin-bottom: 16px;">
    <img src="{{ $siteLogoUrl }}" alt="{{ $appName }}" style="max-height: 56px; width: auto;">
</div>
@endif

# {{ $greeting }}

{{ $thankYouLine }}

**{{ $orderNoLabel }}{{ $orderNo }}**

**{{ $dateLabel }}:** {{ $orderDate }}

---

@foreach ($items as $item)
- **{{ $item['title'] }}** × {{ $item['quantity'] }} — {{ $item['price'] }} {{ $currency }}
@endforeach

---

**{{ $totalLabel }}:** {{ $total }} {{ $currency }}

<x-mail::button :url="$actionUrl">
{{ $actionText }}
</x-mail::button>

{{ $salutation }}
</x-mail::message>
