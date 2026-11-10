<x-layouts.app :title="__('web.thank_you.title')">
    <section class="app-container flex min-h-[60vh] flex-col items-center justify-center py-16 text-center">
        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-success/10 text-success">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h1 class="mt-6 text-3xl font-bold text-[#100f14] sm:text-4xl">{{ __('web.thank_you.title') }}</h1>
        <p class="mt-3 max-w-xl text-sm text-secondary sm:text-base">{{ __('web.thank_you.subtitle') }}</p>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
            <x-button :href="route('home')" variant="primary" size="md">
                {{ __('web.thank_you.cta_home') }}
            </x-button>
            <x-button :href="route('courses.index')" variant="secondary" size="md">
                {{ __('web.thank_you.cta_courses') }}
            </x-button>
            <x-button :href="route('bundles.index')" variant="secondary" size="md">
                {{ __('web.thank_you.cta_bundles') }}
            </x-button>
        </div>
    </section>
</x-layouts.app>
