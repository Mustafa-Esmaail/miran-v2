@if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $siteTitle }}" class="h-15 w-auto" />
@else
    <img src="{{ asset('/img/logo.png') }}" alt="{{ $siteTitle }}" class="h-15 w-auto" />
@endif
