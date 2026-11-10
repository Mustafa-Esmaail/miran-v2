<x-layouts.app :title="__('web.errors.404.title')" fullWidth>
    <section class="w-full bg-[var(--page-muted-bg)]    pb-3">
        <div class="mx-auto w-full text-center">
            <h1 class="text-3xl font-semibold text-[var(--text-strong)] md:text-4xl">

                 {{ __('web.errors.404.title') }}

            </h1>

            <div >
                <img
                    src="{{ asset('img/404.png') }}"
                    alt="{{ __('web.errors.404.image_alt') }}"
                    class="mx-auto h-auto w-full max-w-xl object-contain"
                />
            </div>
            <div class="mt-8">
                <a
                    href="{{ route('home') }}"
                    class="inline-flex items-center justify-center rounded-full border border-[var(--accent-500)] px-6 py-3 text-sm font-semibold text-[var(--accent-500)] transition hover:bg-[var(--accent-10)]"
                >
                    {{ __('web.errors.404.cta') }}
                </a>
            </div>
        </div>
    </section>
</x-layouts.app>
