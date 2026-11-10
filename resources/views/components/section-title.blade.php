@props([
    'title',
    'subtitle' => null,
])

<div class="space-y-2">
    <h2 class="text-2xl font-semibold text-primary sm:text-3xl">{{ $title }}</h2>
    @if ($subtitle)
        <p class="text-sm text-secondary sm:text-base">{{ $subtitle }}</p>
    @endif
</div>
