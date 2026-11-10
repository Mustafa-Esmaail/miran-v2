<div class="full-bleed bg-[var(--page-muted-bg)]">
    <x-banner-title :title="__('web.wishlist.title')" />

    <div class="mx-auto w-full max-w-[1440px] overflow-hidden rounded-[25px] bg-[var(--page-muted-bg)]">
        <section class="px-6 pb-[54px] pt-6">
            <div class="mb-6">
                <h2 class="text-[28px] font-semibold text-left text-[var(--text-ink)]">{{ __('web.wishlist.subtitle') }}</h2>
                    <p class="text-sm text-[var(--text-subtle)]">{{ __('web.wishlist.subtitle') }}</p>
            </div><div class="grid gap-10 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($courseCards as $card)
            <x-course-card :card="$card" />
        @empty
            @if (empty($bundleCards))
                <div class="col-span-full rounded-xl border border-dashed border-default bg-surface p-6 text-center text-sm text-muted">
                    {{ __('web.wishlist.empty') }}
                </div>
            @endif
        @endforelse

        @foreach ($bundleCards as $card)
            <x-bundle-card :card="$card" :link="route('bundles.select-groups', ['bundle' => $card['slug']])" />
        @endforeach
    </div>
</div>



