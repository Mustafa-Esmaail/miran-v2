<div class="app-container py-10 sm:py-12">
    <div class="flex flex-col items-center gap-4 text-center">
        <h1 class="text-3xl font-semibold text-primary">{{ __('web.bundles.title') }}</h1>
        <p class="max-w-2xl text-sm text-secondary sm:text-base">{{ __('web.bundles.subtitle') }}</p>
    </div>

    <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($cards as $card)
            <x-bundle-card :card="$card" :link="route('bundles.select-groups', ['bundle' => $card['slug']])" />
        @empty
            <div class="rounded-2xl border border-border-neutral bg-white p-6 text-center text-sm text-secondary">
                {{ __('web.bundles.empty') }}
            </div>
        @endforelse
    </div>
</div>
