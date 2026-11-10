@props(['class' => 'h-5 w-5'])

<svg viewBox="0 0 24 24" fill="none" {{ $attributes->merge(['class' => $class]) }} aria-hidden="true">
    <path
        d="M11.5 2.75a8.75 8.75 0 106.02 15.1l3.17 3.17a.75.75 0 101.06-1.06l-3.17-3.17A8.75 8.75 0 0011.5 2.75zm0 1.5a7.25 7.25 0 110 14.5 7.25 7.25 0 010-14.5z"
        fill="currentColor"
    ></path>
</svg>
