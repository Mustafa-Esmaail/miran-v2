<div>
@if ($variant === 'dropdown')
    <x-filament::dropdown placement="bottom-start">
        <x-slot name="trigger">
            <x-filament::button color="gray" size="sm">
                <span class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3a9 9 0 100 18 9 9 0 000-18zm0 0c2.8 2.2 4.2 5.1 4.2 9s-1.4 6.8-4.2 9m0-18c-2.8 2.2-4.2 5.1-4.2 9s1.4 6.8 4.2 9m-7.2-9h14.4" />
                    </svg>
                    {{ strtoupper($locale) }}
                </span>
            </x-filament::button>
        </x-slot>
        <x-filament::dropdown.list>
            <x-filament::dropdown.list.item
                tag="a"
                href="{{ route('lang.switch', 'ar') }}"
                rel="nofollow"
            >
                AR
            </x-filament::dropdown.list.item>
            <x-filament::dropdown.list.item
                tag="a"
                href="{{ route('lang.switch', 'en') }}"
                rel="nofollow"
            >
                EN
            </x-filament::dropdown.list.item>
        </x-filament::dropdown.list>
    </x-filament::dropdown>
@else
    <div class="inline-flex items-center gap-2 rounded-full border border-default bg-surface px-2 py-1 text-xs text-muted">
        <svg class="h-4 w-4 text-secondary" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3a9 9 0 100 18 9 9 0 000-18zm0 0c2.8 2.2 4.2 5.1 4.2 9s-1.4 6.8-4.2 9m0-18c-2.8 2.2-4.2 5.1-4.2 9s1.4 6.8 4.2 9m-7.2-9h14.4" />
        </svg>
        <div class="flex items-center rounded-full bg-muted p-0.5">
            <a
                href="{{ route('lang.switch', 'en') }}"
                class="{{ $locale === 'en' ? 'bg-surface text-primary shadow-sm' : 'text-secondary' }} rounded-full px-2 py-1 transition"
                rel="nofollow"
            >
                EN
            </a>
            <a
                href="{{ route('lang.switch', 'ar') }}"
                class="{{ $locale === 'ar' ? 'bg-surface text-primary shadow-sm' : 'text-secondary' }} rounded-full px-2 py-1 transition"
                rel="nofollow"
            >
                AR
            </a>
        </div>
    </div>
@endif
</div>
