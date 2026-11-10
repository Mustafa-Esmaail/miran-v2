@props(['class' => 'h-5 w-5'])

<svg viewBox="0 0 24 24" fill="none" {{ $attributes->merge(['class' => $class]) }} aria-hidden="true">
    <path
        d="M12 2a8 8 0 00-8 8v5a2 2 0 002 2h2v-6H6V10a6 6 0 1112 0v1h-2v6h2a2 2 0 002-2v-5a8 8 0 00-8-8z"
        fill="currentColor"
    ></path>
    <path d="M10 21h4a2 2 0 002-2v-1h-8v1a2 2 0 002 2z" fill="currentColor"></path>
</svg>
