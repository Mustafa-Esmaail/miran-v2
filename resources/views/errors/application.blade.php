<x-layouts.app :title="$error['title']" fullWidth>

    <section class="w-full bg-[var(--page-muted-bg)] py-12">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 text-center sm:px-6">
            <div
                class="mx-auto flex w-full max-w-xl flex-col gap-4 rounded-[32px] border border-black/8 bg-white p-8 shadow-[0_16px_40px_rgba(16,15,20,0.08)]">
                <span
                    class="mx-auto inline-flex rounded-full bg-[#99745c]/12 px-4 py-2 text-xs font-semibold tracking-[0.18em] text-[#99745c]">
                    {{ $error['code'] }}
                </span>

                <h1 class="text-3xl font-semibold text-[var(--text-strong)] md:text-4xl">
                    {{ $error['title'] }}
                </h1>

                <p class="text-base leading-8 text-[var(--text-soft)]">
                    {{ $error['message'] }}
                </p>

                @if (filled($error['details']))
                    <div class="rounded-3xl bg-[#100f14] px-5 py-4 text-left text-sm leading-7 text-white/90"
                        dir="ltr">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-[0.18em] text-white/60">
                            {{ __('web.errors.generic.details_label') }}
                        </p>
                        <p class="break-words">{{ $error['details'] }}</p>
                    </div>
                @endif

                <div class="pt-2">
                    <a href="{{ route('home') }}"
                        class="inline-flex items-center justify-center rounded-full border border-[var(--accent-500)] px-6 py-3 text-sm font-semibold text-[var(--accent-500)] transition hover:bg-[var(--accent-10)]">
                        {{ __('web.errors.generic.cta') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
