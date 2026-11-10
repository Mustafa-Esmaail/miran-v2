<x-layouts.app
    :title="__('auth.password_reset')"
    :hide-footer="true"
    :full-width="true"
    :nav-links="$loginNavLinks"
>
    <section class="relative overflow-hidden bg-[var(--banner-bg)] text-white">
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.12]"
            style="background-image: linear-gradient(rgba(255,255,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 120px 120px;"
        ></div>
        <div
            class="pointer-events-none absolute -top-[120px] start-[-220px] h-[520px] w-[520px] rounded-full blur-2xl"
            style="background: radial-gradient(circle, rgba(113,150,147,0.35), rgba(113,150,147,0));"
        ></div>

        <div class="mx-auto max-w-[1440px] px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
            <div class="grid gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
                <div class="relative">
                    <div class="max-w-lg space-y-4">
                        <h1 class="text-3xl font-semibold sm:text-4xl">{{ __('web.auth.side_heading') }}</h1>
                        <p class="text-base text-white/80 sm:text-lg">
                            {{ __('web.auth.side_subheading') }}
                        </p>
                    </div>

                    <div class="relative mt-10">
                        <div class="relative w-full max-w-[460px] rounded-[28px] bg-white p-4 text-[var(--text-primary)] shadow-[30px_0px_90px_rgba(113,150,147,0.18)]">
                            <div class="relative h-48 overflow-hidden rounded-2xl bg-[var(--auth-image-placeholder)]">
                                @if ($primaryCourse && $primaryCourseImageUrl)
                                    <img class="h-full w-full object-cover" src="{{ $primaryCourseImageUrl }}" alt="{{ $primaryCourse->title }}">
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-[var(--auth-gradient-start)] to-[var(--auth-gradient-end)]"></div>
                                @endif
                                <div class="absolute start-4 bottom-4 rounded-xl bg-gradient-to-b from-[color-mix(in srgb, var(--auth-gradient-end) 60%, transparent)] to-[color-mix(in srgb, var(--auth-gradient-start) 60%, transparent)] px-4 py-2 text-xs font-bold text-white backdrop-blur">
                                    {{ $primaryCourse?->category?->name ?? __('web.auth.primary_category') }}
                                </div>
                                <div class="absolute end-4 top-4 flex flex-col gap-2">
                                    <button class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[var(--nav-brand-dark)] shadow-[0_4px_10px_rgba(0,0,0,0.12)]">
                                        <x-icon.heart class="h-4 w-4" />
                                    </button>
                                    <button class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[var(--nav-brand-dark)] shadow-[0_4px_10px_rgba(0,0,0,0.12)]">
                                        <x-icon.bag class="h-4 w-4" />
                                    </button>
                                    <button class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[var(--nav-brand-dark)] shadow-[0_4px_10px_rgba(0,0,0,0.12)]">
                                        <x-icon.share class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <div class="flex items-start justify-between gap-4">
                                    <p class="text-lg font-bold text-[var(--text-strong)]">
                                        {{ $primaryCourse?->title ?? __('web.auth.primary_title') }}
                                    </p>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-[var(--accent-500)]">
                                            {{ $primaryCourse?->final_price ? number_format($primaryCourse->final_price) : '1,799' }}
                                        </p>
                                        @if ($primaryCourse?->price && $primaryCourse?->final_price && $primaryCourse->final_price < $primaryCourse->price)
                                            <p class="text-xs text-[var(--text-placeholder-soft)] line-through">
                                                {{ number_format($primaryCourse->price) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs text-[var(--text-muted-dark)]">
                                    <span class="rounded-3xl bg-[var(--surface-soft)] px-3 py-1.5">{{ __('web.courses.level_beginner') }}</span>
                                    <span class="rounded-3xl bg-[color-mix(in srgb, var(--surface-subtle) 80%, transparent)] px-3 py-1.5">
                                        {{ $primaryCourse?->lessons_count ?? 17 }} {{ __('web.courses.lessons_label') }}
                                    </span>
                                    <span class="rounded-3xl bg-[color-mix(in srgb, var(--surface-subtle) 80%, transparent)] px-3 py-1.5">
                                        {{ $primaryCourse?->duration_hours ?? 2 }} {{ __('web.course.hours') }} {{ $primaryCourse?->duration_group ?? __('web.auth.primary_duration') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="relative -mt-20 ms-14 w-full max-w-[460px] rounded-[28px] bg-white p-4 text-[var(--text-primary)] shadow-[-5px_-4px_22px_rgba(0,0,0,0.12)]">
                            <div class="relative h-48 overflow-hidden rounded-2xl bg-[var(--auth-image-placeholder)]">
                                @if ($secondaryCourse && $secondaryCourseImageUrl)
                                    <img class="h-full w-full object-cover" src="{{ $secondaryCourseImageUrl }}" alt="{{ $secondaryCourse->title }}">
                                @else
                                    <div class="h-full w-full bg-gradient-to-br from-[var(--auth-gradient-start)] to-[var(--auth-gradient-end)]"></div>
                                @endif
                                <div class="absolute start-4 bottom-4 rounded-xl bg-gradient-to-b from-[color-mix(in srgb, var(--auth-gradient-end) 60%, transparent)] to-[color-mix(in srgb, var(--auth-gradient-start) 60%, transparent)] px-4 py-2 text-xs font-bold text-white backdrop-blur">
                                    {{ $secondaryCourse?->category?->name ?? __('web.auth.secondary_category') }}
                                </div>
                                <div class="absolute end-4 top-4 flex flex-col gap-2">
                                    <button class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[var(--nav-brand-dark)] shadow-[0_4px_10px_rgba(0,0,0,0.12)]">
                                        <x-icon.heart class="h-4 w-4" />
                                    </button>
                                    <button class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[var(--nav-brand-dark)] shadow-[0_4px_10px_rgba(0,0,0,0.12)]">
                                        <x-icon.bag class="h-4 w-4" />
                                    </button>
                                    <button class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[var(--nav-brand-dark)] shadow-[0_4px_10px_rgba(0,0,0,0.12)]">
                                        <x-icon.share class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                            <div class="mt-4 space-y-3">
                                <div class="flex items-start justify-between gap-4">
                                    <p class="text-lg font-semibold text-[var(--text-strong)]">
                                        {{ $secondaryCourse?->title ?? __('web.auth.secondary_title') }}
                                    </p>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-[var(--accent-500)]">
                                            {{ $secondaryCourse?->final_price ? number_format($secondaryCourse->final_price) : '1,799' }}
                                        </p>
                                        @if ($secondaryCourse?->price && $secondaryCourse?->final_price && $secondaryCourse->final_price < $secondaryCourse->price)
                                            <p class="text-xs text-[var(--text-placeholder-soft)] line-through">
                                                {{ number_format($secondaryCourse->price) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2 text-xs text-[var(--text-muted-dark)]">
                                    <span class="rounded-3xl bg-[var(--surface-soft)] px-3 py-1.5">{{ __('web.courses.level_advanced') }}</span>
                                    <span class="rounded-3xl bg-[color-mix(in srgb, var(--surface-subtle) 80%, transparent)] px-3 py-1.5">
                                        {{ $secondaryCourse?->lessons_count ?? 12 }} {{ __('web.courses.lessons_label') }}
                                    </span>
                                    <span class="rounded-3xl bg-[color-mix(in srgb, var(--surface-subtle) 80%, transparent)] px-3 py-1.5">
                                        {{ $secondaryCourse?->duration_hours ?? 3 }} {{ __('web.course.hours') }} {{ $secondaryCourse?->duration_group ?? __('web.auth.secondary_duration') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="relative -mt-14 ms-44 w-full max-w-[280px] rounded-2xl bg-white p-4 text-[var(--text-primary)] shadow-[-5px_-4px_22px_rgba(0,0,0,0.08)]">
                            <p class="text-base font-semibold">{{ __('web.home.hero_happy_students') }}</p>
                            <div class="mt-1 flex items-center gap-2 text-xs">
                                <span class="font-medium">4.5</span>
                                <span class="text-[var(--text-quiet)]">({{ $studentCount }})</span>
                                <div class="h-[13px] w-[14px] rounded-full bg-[var(--rating-accent)]"></div>
                            </div>
                            <div class="mt-4 flex -space-x-4">
                                @for ($i = 0; $i < 5; $i++)
                                    <div class="h-[38px] w-[38px] rounded-full bg-[var(--avatar-muted)]"></div>
                                @endfor
                                <div class="flex h-[38px] w-[38px] items-center justify-center rounded-full bg-[var(--rating-accent)] text-xs font-semibold text-[var(--text-primary)]">
                                    2K+
                                </div>
                            </div>
                        </div>

                        <div class="pointer-events-none absolute -start-6 top-24 hidden h-14 w-14 rounded-full bg-white/90 text-[var(--nav-brand-dark)] lg:flex lg:items-center lg:justify-center">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M12 3L2 8l10 5 8-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                <path d="M6 10.5V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                        </div>
                        <div class="pointer-events-none absolute -bottom-2 start-2 hidden h-14 w-14 rounded-full bg-white/90 text-[var(--accent-500)] lg:flex lg:items-center lg:justify-center">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                            </svg>
                        </div>
                        <div class="pointer-events-none absolute end-[-40px] bottom-28 hidden h-14 w-14 rounded-full bg-white/90 text-[var(--nav-brand-dark)] lg:flex lg:items-center lg:justify-center">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M4 9h16v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V9Z" stroke="currentColor" stroke-width="1.5"></path>
                                <path d="M8 9V6a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="1.5"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="mx-auto w-full max-w-[560px] rounded-3xl bg-white text-[var(--text-primary)] shadow-[0_30px_80px_rgba(0,0,0,0.2)]">
                        <div class="flex h-full flex-col justify-between px-8 py-10 sm:px-12">
                            <div class="space-y-10">
                                <div>
                                    <p class="text-lg font-semibold text-[var(--accent-500)]">{{ __('auth.password_reset') }}</p>
                                    <p class="text-[40px] font-semibold text-[var(--text-primary)]">{{ __('web.auth.forgot_password_title') }}</p>
                                    <p class="mt-2 text-sm text-[var(--text-muted-dark)]">{{ __('auth.password_reset_help') }}</p>
                                </div>

                                @if (session('status'))
                                    <div class="rounded-2xl border border-[var(--nav-border)] bg-[var(--surface-soft)] px-4 py-3 text-sm text-[var(--text-primary)]">
                                        {{ session('status') }}
                                    </div>
                                @endif

                                <form class="space-y-6" method="POST" action="{{ route('password.email') }}">
                                    @csrf

                                    <div class="space-y-2">
                                        <label class="text-sm text-[var(--text-primary)]" for="email">{{ __('auth.email') }}</label>
                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            value="{{ old('email') }}"
                                            class="h-[52px] w-full rounded-[100px] border border-[var(--border-soft)] px-6 text-base text-[var(--text-primary)] placeholder:text-[var(--text-quiet)] focus:outline-none"
                                            required
                                            autofocus
                                        >
                                        @error('email')
                                            <p class="text-xs text-alert">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button type="submit" class="w-full rounded-[32px] bg-[var(--accent-500)] px-6 py-3 text-lg text-white">
                                        {{ __('auth.send_reset_link') }}
                                    </button>
                                </form>
                            </div>

                            <div class="mt-10 space-y-8">
                                <div class="flex items-center gap-[11px]">
                                    <div class="h-px flex-1 bg-[var(--border-soft-2)]"></div>
                                    <span class="text-base text-[var(--text-faded-strong)]">{{ __('web.auth.or_label') }}</span>
                                    <div class="h-px flex-1 bg-[var(--border-soft-2)]"></div>
                                </div>
                                <div class="flex flex-wrap items-center gap-4">
                                    <button type="button" class="flex h-[72px] w-[72px] items-center justify-center rounded-lg border border-[var(--border-soft-2)] text-[var(--text-primary)]">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M21.6 12.27c0-.73-.07-1.43-.2-2.11H12v3.99h5.4a4.62 4.62 0 0 1-2 3.03v2.52h3.24c1.9-1.75 2.96-4.34 2.96-7.43Z" fill="var(--brand-google-blue)"></path>
                                            <path d="M12 22c2.7 0 4.97-.9 6.62-2.45l-3.24-2.52c-.9.6-2.06.96-3.38.96-2.6 0-4.8-1.75-5.59-4.1H3.06v2.58A9.99 9.99 0 0 0 12 22Z" fill="var(--brand-google-green)"></path>
                                            <path d="M6.41 13.89a5.99 5.99 0 0 1 0-3.78V7.53H3.06a9.99 9.99 0 0 0 0 8.94l3.35-2.58Z" fill="var(--brand-google-yellow)"></path>
                                            <path d="M12 6.02c1.47 0 2.79.5 3.83 1.48l2.87-2.87C16.96 3.05 14.7 2 12 2A9.99 9.99 0 0 0 3.06 7.53l3.35 2.58C7.2 7.77 9.4 6.02 12 6.02Z" fill="var(--brand-google-red)"></path>
                                        </svg>
                                    </button>
                                    <button type="button" class="flex h-[72px] w-[72px] items-center justify-center rounded-lg border border-[var(--border-soft-2)] text-[var(--text-primary)]">
                                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M16.53 12.08c-.02-2.14 1.76-3.17 1.84-3.22-1-1.46-2.56-1.66-3.11-1.69-1.33-.14-2.6.77-3.28.77-.68 0-1.74-.75-2.86-.73-1.47.02-2.83.86-3.59 2.19-1.53 2.66-.39 6.6 1.1 8.76.73 1.06 1.6 2.24 2.74 2.2 1.1-.04 1.52-.71 2.85-.71 1.33 0 1.71.71 2.86.69 1.18-.02 1.93-1.07 2.65-2.13.84-1.23 1.18-2.42 1.2-2.48-.03-.01-2.32-.89-2.35-3.65Z" fill="var(--brand-apple)"></path>
                                            <path d="M14.73 5.18c.6-.73 1.01-1.75.9-2.76-.86.03-1.9.57-2.52 1.29-.56.64-1.06 1.67-.93 2.65.96.08 1.95-.49 2.55-1.18Z" fill="var(--brand-apple)"></path>
                                        </svg>
                                    </button>
                                    <button type="button" class="flex h-[72px] w-[72px] items-center justify-center rounded-lg border border-[var(--border-soft-2)] text-[var(--text-primary)]">
                                        <x-icon.instagram class="h-8 w-8 text-[var(--text-primary)]" />
                                    </button>
                                </div>
                                <div class="flex items-center gap-1 text-base text-[var(--text-muted-dark)]">
                                    <span>{{ __('web.auth.remembered') }}</span>
                                    <a class="font-semibold text-[var(--accent-500)]" href="{{ route('login') }}">
                                        {{ __('auth.login') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>

