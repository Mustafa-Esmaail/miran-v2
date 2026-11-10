<x-layouts.app :title="$page->title" :meta-title="$metaTitle ?? null" :meta-description="$metaDescription ?? null" :og-image-url="$ogImageUrl ?? null">
    <div class="full-bleed bg-[#eff3f3]">
        <section class="relative overflow-hidden bg-brand-dark text-white">
            <div class="pointer-events-none absolute inset-0 hero-grid-overlay opacity-[0.30]"></div>
            <div class="pointer-events-none absolute -top-32 -start-56 h-[520px] w-[520px] rounded-full blur-2xl hero-teal-glow"></div>

            <div class="app-container relative py-16 sm:py-20 lg:py-24">
                <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr]">
                    <div class="space-y-10">
                        <div class="space-y-5">
                            <h1 class="text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">
                                {{ __('web.about.hero_title') }}
                            </h1>
                            <p class="max-w-xl text-sm text-hero-muted sm:text-base">
                                {{ __('web.about.hero_subtitle') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-6 rounded-2xl border border-white/10 bg-white/5 px-6 py-5">
                            <div class="min-w-[140px] flex-1 text-center">
                                <p class="text-2xl font-semibold">{{ __('web.about.stats_courses_value') }}</p>
                                <p class="mt-1 text-sm text-hero-muted">{{ __('web.about.stats_courses') }}</p>
                            </div>
                            <div class="hidden h-12 w-px bg-white/15 sm:block"></div>
                            <div class="min-w-[140px] flex-1 text-center">
                                <p class="text-2xl font-semibold">{{ __('web.about.stats_mentors_value') }}</p>
                                <p class="mt-1 text-sm text-hero-muted">{{ __('web.about.stats_mentors') }}</p>
                            </div>
                            <div class="hidden h-12 w-px bg-white/15 sm:block"></div>
                            <div class="min-w-[140px] flex-1 text-center">
                                <p class="text-2xl font-semibold">{{ __('web.about.stats_reviews_value') }}</p>
                                <p class="mt-1 text-sm text-hero-muted">{{ __('web.about.stats_reviews') }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-md lg:max-w-none">
                        <div class="pointer-events-none absolute -top-10 -end-8 h-40 w-40 rounded-tr-[50px] bg-[#ff8057]"></div>
                        <div class="pointer-events-none absolute -bottom-10 start-8 h-56 w-56 rounded-full bg-[#ffd05b]"></div>
                        <div class="pointer-events-none absolute -bottom-4 start-24 h-44 w-44 rounded-full bg-[#ff8057]/40"></div>

                        <img
                            src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80"
                            alt="{{ __('web.about.learner_alt') }}"
                            class="relative z-10 h-[420px] w-full rounded-3xl object-cover shadow-card sm:h-[480px]"
                        />

                        <div class="absolute -bottom-6 start-6 z-20 rounded-2xl bg-white px-5 py-4 shadow-card">
                            <p class="text-sm font-medium text-muted">{{ __('web.about.level_label') }}</p>
                            <div class="mt-3 h-2 w-48 rounded-full bg-gradient-to-r from-[#99745c] via-[#c0945b]/70 to-transparent"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="app-container py-14 sm:py-16 lg:py-20">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-2xl font-bold text-primary sm:text-3xl">{{ __('web.about.impact_title') }}</h2>
                <p class="mt-3 text-sm text-secondary sm:text-base">
                    {{ __('web.about.impact_body') }}
                </p>
            </div>

            <div class="mt-12 grid gap-8 lg:grid-cols-2">
                <div class="relative rounded-2xl bg-white p-8 shadow-card">
                    <div class="flex items-start gap-5">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-b from-[#374746] to-[#86adab] text-white">
                            <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none">
                                <path d="M5 12l4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-primary">{{ __('web.about.vision_title') }}</h3>
                            <p class="mt-2 text-sm text-secondary">
                                {{ __('web.about.vision_body') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="relative rounded-2xl bg-white p-8 shadow-card">
                    <div class="flex items-start gap-5">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-b from-[#374746] to-[#86adab] text-white">
                            <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none">
                                <path d="M6 7h12M6 12h7M6 17h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-primary">{{ __('web.about.mission_title') }}</h3>
                            <p class="mt-2 text-sm text-secondary">
                                {{ __('web.about.mission_body') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="app-container py-14 sm:py-16 lg:py-20">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-2xl font-bold text-primary sm:text-3xl">{{ __('web.about.about_title') }}</h2>
                <p class="mt-3 text-sm text-secondary sm:text-base">
                    {{ __('web.about.about_body') }}
                </p>
            </div>

            <div class="mt-12 space-y-14">
                <div class="grid items-center gap-10 lg:grid-cols-[0.45fr_0.55fr]">
                    <div class="relative">
                        <div class="pointer-events-none absolute -top-6 -end-6 h-52 w-52 rounded-tl-[90px] bg-[#ffd05b]"></div>
                        <img
                            src="https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=800&q=80"
                            alt="{{ __('web.about.team_session_alt') }}"
                            class="relative z-10 h-[360px] w-full rounded-3xl object-cover shadow-card"
                        />
                    </div>
                    <p class="text-sm text-secondary sm:text-base">
                        {{ __('web.about.story_block_one') }}
                    </p>
                </div>

                <div class="grid items-center gap-10 lg:grid-cols-[0.55fr_0.45fr]">
                    <p class="text-sm text-secondary sm:text-base">{{ __('web.about.story_block_two') }}</p>
                    <div class="relative lg:order-last">
                        <div class="pointer-events-none absolute -top-6 -start-6 h-56 w-56 rounded-full bg-[#ffd05b]"></div>
                        <div class="pointer-events-none absolute -top-1 start-10 h-44 w-44 rounded-full bg-[#ff8057]/40"></div>
                        <img
                            src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80"
                            alt="{{ __('web.about.instructor_alt') }}"
                            class="relative z-10 h-[360px] w-full rounded-3xl object-cover shadow-card"
                        />
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-[#f1f1f1] bg-white py-12 sm:py-16">
            <div class="app-container">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-primary sm:text-3xl">{{ __('web.about.partners_title') }}</h2>
                </div>
                @php
                    $partnerLogos = [
                        'https://themiran.net/wp-content/uploads/2025/02/contact-mail_3095583-1.png',
                        'https://themiran.net/wp-content/uploads/2023/11/New_Project-removebg-preview-e1699446752333.png',
                        'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg',
                        'https://images.unsplash.com/photo-1520607162513-77705c0f0d4a?auto=format&fit=crop&w=400&q=80',
                        'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=400&q=80',
                        'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?auto=format&fit=crop&w=400&q=80',
                    ];
                @endphp
                <div class="mt-10 grid grid-cols-2 items-center gap-6 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ($partnerLogos as $logo)
                        <img src="{{ $logo }}" alt="{{ __('web.about.partner_logo_alt') }}" class="partner-logo mx-auto h-12">
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</x-layouts.app>
