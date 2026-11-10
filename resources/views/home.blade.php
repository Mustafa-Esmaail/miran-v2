<x-layouts.app>
    <livewire:home.hero-slider />

    @php
        $categoryHighlights = collect($vm->categories)->take(4)->values();
        $statPrimary = $vm->statsItems[0] ?? null;
        $statSecondary = $vm->statsItems[1] ?? null;
        $brandHighlight = __('web.home.brand_highlight');
    @endphp

    <section id="categories" class="py-6">
        <div class="app-container">
            <div
                class="relative overflow-hidden rounded-2xl bg-white px-6 py-6 shadow-card sm:px-10"
            >
                {{-- radial background accents to match Figma card --}}
                <div
                    class="pointer-events-none absolute -right-40 -top-40 h-80 w-80 rounded-full opacity-40 category-glow-teal"
                ></div>
                <div
                    class="pointer-events-none absolute -bottom-40 -left-40 h-80 w-80 rounded-full opacity-40 category-glow-sand"
                ></div>

                <div class="relative space-y-8">
                    <div class="mx-auto max-w-3xl text-center">
                        <p class="text-sm font-medium text-brand-accent uppercase tracking-wide">
                            {{ __('web.home.featured_categories_label') }}
                        </p>
                        <h2 class="mt-3 text-[28px] font-bold leading-tight text-neutral-text sm:text-[32px] md:text-[36px]">
                            {!! str_replace(
                                $brandHighlight,
                                '<span class="text-brand-accent">'.$brandHighlight.'</span>',
                                e($vm->categoriesTitle),
                            ) !!}
                        </h2>
                        <p class="mt-3 text-sm text-secondary sm:text-base">
                            {{ $vm->categoriesSubtitle }}
                        </p>
                    </div>

                    {{-- primary category highlight cards (grid) --}}
                    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ($categoryHighlights as $index => $category)
                            <a
                                href="{{ route('courses.index', ['categoryId' => $category['id']]) }}"
                                class="group relative flex flex-col justify-between rounded-2xl bg-white/90 p-6 shadow-card ring-1 ring-border-neutral/70 transition hover:-translate-y-1 hover:bg-white hover:shadow-xl"
                            >
                                <div class="flex items-center justify-between gap-4">
                                    <div
                                    class="flex h-12 w-12 items-center justify-center rounded-[40px] bg-linear-to-b from-brand-dark to-[#4F716A] text-white"
                                    >
                                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M4 4h16v16H4z" opacity="0.2" />
                                            <path d="M7 7h10v2H7zM7 11h6v2H7zM7 15h8v2H7z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-semibold text-brand-accent">
                                        0{{ $index + 1 }}
                                    </span>
                                </div>

                                <div class="mt-6 space-y-2">
                                    <h3 class="text-lg font-semibold text-neutral-text">
                                        {{ $category['name'] }}
                                    </h3>
                                    <p class="text-xs text-secondary">
                                        {{ __('web.home.course_count', ['count' => $category['courses_count']]) }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- secondary category chips row --}}
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        @foreach ($vm->categories as $category)
                            <a
                                href="{{ route('courses.index', ['categoryId' => $category['id']]) }}"
                                class="inline-flex items-center justify-center rounded-pill bg-neutral-bg px-6 py-2.5 text-sm font-medium text-neutral-text shadow-sm ring-1 ring-border-neutral/70 transition hover:bg-white hover:text-brand-accent"
                            >
                                {{ $category['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <livewire:home.featured-courses />

    <livewire:home.special-bundles />

    <section id="about" class="py-6">
        <div class="app-container">
            <div class="grid gap-10 lg:grid-cols-[1.05fr_1fr] lg:items-center">
                <div class="relative">
                    <div class="relative overflow-hidden rounded-[40px] bg-[#443131] pb-[110%] sm:pb-[85%]">
                        <img
                            src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=900&q=80"
                            alt="{{ __('web.home.about_image_alt') }}"
                            class="absolute inset-0 h-full w-full object-cover"
                        />
                    </div>
                    @if ($statPrimary)
                        <div class="absolute -start-4 bottom-8 rounded-2xl bg-white/95 px-5 py-4 shadow-soft">
                            <p class="text-sm text-neutral-muted">{{ $statPrimary['label'] }}</p>
                            <p class="text-2xl font-semibold text-neutral-text">{{ $statPrimary['value'] }}</p>
                        </div>
                    @endif
                    @if ($statSecondary)
                        <div class="absolute end-4 top-8 rounded-2xl bg-white/95 px-5 py-4 shadow-soft">
                            <p class="text-sm text-neutral-muted">{{ $statSecondary['label'] }}</p>
                            <div class="mt-2 h-2 w-40 rounded-full bg-neutral-bg">
                                <div class="h-2 w-2/3 rounded-full bg-brand-accent"></div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="space-y-5">
                    <h2 class="text-[28px] font-bold leading-tight text-neutral-text sm:text-[32px]">
                        {{ $vm->aboutTitle }}
                    </h2>
                    <p class="text-sm text-neutral-muted sm:text-base">{{ $vm->aboutSubtitle }}</p>
                    <div class="rich-content text-sm sm:text-base">{!! $vm->aboutBody !!}</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach ($vm->aboutPoints as $point)
                            <div class="rounded-2xl border border-border-neutral bg-white p-4 shadow-card">
                                <p class="text-sm font-semibold text-neutral-text">{{ $point['title'] }}</p>
                                <p class="mt-2 text-xs text-neutral-muted">{{ $point['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="rounded-2xl border border-border-neutral bg-white p-5 shadow-card">
                        <p class="text-xs font-semibold uppercase tracking-wide text-neutral-muted">
                            {{ $vm->aboutCardLabel }}
                        </p>
                        <p class="mt-2 text-lg font-semibold text-neutral-text">{{ $vm->aboutCardTitle }}</p>
                        <div class="rich-content mt-2 text-sm">{!! $vm->aboutCardBody !!}</div>
                    </div>
                    <div class="flex flex-wrap gap-4 pt-2">
                        <a href="{{ route('courses.index') }}" class="rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white shadow-sm hover:brightness-110">
                            {{ $vm->viewCoursesLabel }}
                        </a>
                        <a href="#contact" class="rounded-full border border-border-neutral bg-white px-6 py-3 text-sm font-semibold text-neutral-text shadow-sm hover:text-brand-accent">
                            {{ $vm->contactRequestLabel }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="why-us" class="py-6">
        <div class="app-container">
            <div class="mx-auto max-w-3xl text-center">
                <h2 class="text-[28px] font-bold text-neutral-text sm:text-[32px]">
                    {{ $vm->whyTitle }}
                </h2>
                <p class="mt-2 text-sm text-neutral-muted sm:text-base">
                    {{ $vm->whySubtitle }}
                </p>
            </div>
            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vm->whyPoints as $point)
                    <div class="rounded-2xl border border-border-neutral bg-white p-6 shadow-card">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-linear-to-b from-brand-dark to-[#4F716A] text-white">
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3 6 6 .9-4.5 4.3 1.1 6.1L12 17l-5.6 2.3 1.1-6.1L3 8.9 9 8z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-neutral-text">{{ $point['title'] }}</h3>
                        <p class="mt-2 text-sm text-secondary">{{ $point['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="partners" class="py-6">
        <div class="app-container">
            <div class="text-center">
                <h2 class="text-[28px] font-bold text-neutral-text sm:text-[32px]">
                    {{ $vm->partnersTitle }}
                </h2>
                <p class="mt-2 text-sm text-neutral-muted sm:text-base">
                    {{ $vm->partnersSubtitle }}
                </p>
            </div>
            <div class="mt-10 overflow-hidden rounded-2xl border border-border-neutral bg-white px-6 py-8 shadow-card">
                <div class="logo-marquee">
                    <div class="logo-track">
                        @foreach (range(1, 2) as $repeat)
                            @foreach ($vm->partnerLogos as $logo)
                                <img src="{{ $logo }}" alt="{{ __('web.home.partner_logo_alt') }}" class="partner-logo">
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <livewire:home.testimonials-carousel />

    <section id="corporate" class="relative overflow-hidden bg-section py-6">
        <div
            class="absolute inset-0 opacity-60 corporate-ambient"
        ></div>
        <div class="app-container relative">
            <div class="rounded-2xl border border-border-neutral bg-white/85 px-6 py-6 text-center shadow-card sm:px-10">
                <h2 class="text-[28px] font-bold text-neutral-text sm:text-[32px]">
                    {{ $vm->corporateTitle }}
                </h2>
                <p class="mt-4 text-sm text-neutral-muted sm:text-base">
                    {{ $vm->corporateSubtitle }}
                </p>
                <div class="mt-6 flex flex-wrap justify-center gap-4">
                    <a href="#contact" class="rounded-full bg-brand-accent px-8 py-3 text-sm font-semibold text-white shadow-sm hover:brightness-110">
                        {{ $vm->contactRequestLabel }}
                    </a>
                    <a href="{{ route('courses.index') }}" class="rounded-full border border-border-neutral bg-white px-8 py-3 text-sm font-semibold text-neutral-text shadow-sm hover:text-brand-accent">
                        {{ $vm->viewCoursesLabel }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="stats" class="py-6">
        <div class="app-container">
            <div class="grid gap-6 sm:grid-cols-3">
                @foreach ($vm->statsItems as $item)
                    <div class="rounded-2xl border border-border-neutral bg-white p-6 text-center shadow-card">
                        <div class="text-3xl font-semibold text-neutral-text">{{ $item['value'] }}</div>
                        <div class="mt-2 text-xs text-neutral-muted">{{ $item['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <livewire:home.lead-form />

    <section id="complaints" class="py-6">
        <div class="app-container">
            <div class="grid gap-6 rounded-2xl border border-border-neutral bg-section p-8 shadow-card lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                <div>
                    <p class="text-sm font-semibold text-brand-accent">{{ $vm->complaintsTag }}</p>
                    <h2 class="mt-3 text-[24px] font-bold text-neutral-text sm:text-[28px]">
                        {{ $vm->complaintsTitle }}
                    </h2>
                    <p class="mt-2 text-sm text-neutral-muted sm:text-base">
                        {{ $vm->complaintsSubtitle }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="mailto:{{ $vm->contactEmail }}" class="rounded-full bg-brand-accent px-6 py-3 text-sm font-semibold text-white">
                        {{ $vm->complaintsCta }}
                    </a>
                    <a href="#contact" class="rounded-full border border-border-neutral px-6 py-3 text-sm font-semibold text-neutral-text">
                        {{ $vm->contactRequestLabel }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <livewire:home.course-quick-view-modal />
</x-layouts.app>
