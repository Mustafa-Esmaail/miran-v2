@props([
    'phone' => null,
    'whatsapp' => null,
    'message' => null,
    'raised' => false,
])

@php
    $telHref = $phone ? 'tel:'.preg_replace('/[^\d+]/', '', $phone) : null;
    $whatsappHref = $whatsapp ? 'https://wa.me/'.$whatsapp.($message ? '?text='.rawurlencode($message) : '') : null;
@endphp

@if ($telHref || $whatsappHref)
    <div x-data="{ open: false }" class="fixed {{ $raised ? 'bottom-24 lg:bottom-8' : 'bottom-8' }} right-8 z-50 flex flex-col items-end gap-3">

        <div x-show="open" x-transition x-cloak class="flex flex-col items-end gap-3">
            @if ($whatsappHref)
                <a
                    href="{{ $whatsappHref }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="{{ __('web.floating_contact.whatsapp') }}"
                    title="{{ __('web.floating_contact.whatsapp') }}"
                    class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition-all duration-200 hover:scale-110 hover:shadow-2xl"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6" fill="currentColor">
                        <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.87 9.87 0 0 0 4.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 1.67c2.19 0 4.25.85 5.8 2.41a8.2 8.2 0 0 1 2.42 5.83c0 4.55-3.7 8.25-8.24 8.25a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.18 8.18 0 0 1-1.26-4.38c0-4.55 3.71-8.25 8.26-8.25Zm-3.94 4.7c-.15 0-.4.06-.61.3-.21.24-.8.79-.8 1.92 0 1.13.82 2.22.93 2.37.12.15 1.62 2.5 3.99 3.4 1.97.75 2.37.6 2.8.56.43-.04 1.39-.57 1.58-1.11.2-.55.2-1.02.14-1.11-.06-.1-.22-.16-.46-.28-.24-.12-1.4-.69-1.62-.77-.22-.08-.37-.12-.53.12-.15.24-.6.77-.74.93-.14.15-.27.17-.51.06-.24-.12-1.01-.37-1.93-1.19-.71-.64-1.2-1.42-1.34-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.37-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.53-1.3-.74-1.77-.19-.46-.39-.4-.53-.4Z" />
                    </svg>
                </a>
            @endif

            @if ($telHref)
                <a
                    href="{{ $telHref }}"
                    aria-label="{{ __('web.floating_contact.call') }}"
                    title="{{ __('web.floating_contact.call') }}"
                    class="flex h-12 w-12 cursor-pointer items-center justify-center rounded-full bg-[#99745c] text-white shadow-lg transition-all duration-200 hover:scale-110 hover:shadow-2xl"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.98.8l.7 3.5a1 1 0 01-.5 1.09L7.1 9.5a11.04 11.04 0 006.4 6.4l1.11-1.36a1 1 0 011.09-.5l3.5.7a1 1 0 01.8.98V19a2 2 0 01-2 2h-1C9.16 21 3 14.84 3 7V5z" />
                    </svg>
                </a>
            @endif
        </div>

        <button
            type="button"
            x-on:click="open = !open"
            aria-label="{{ __('web.floating_contact.close') }}"
            class="flex h-14 w-14 cursor-pointer items-center justify-center rounded-full bg-[#374746] text-white shadow-xl transition-all duration-200 hover:scale-110 hover:bg-[#374746]/90 hover:shadow-2xl"
        >
            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h2.28a1 1 0 01.98.8l.7 3.5a1 1 0 01-.5 1.09L7.1 9.5a11.04 11.04 0 006.4 6.4l1.11-1.36a1 1 0 011.09-.5l3.5.7a1 1 0 01.8.98V19a2 2 0 01-2 2h-1C9.16 21 3 14.84 3 7V5z" />
            </svg>
            <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
@endif
