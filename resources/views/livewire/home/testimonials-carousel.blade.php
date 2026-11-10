@php
    $settings = app(\App\Services\Settings\SettingsService::class);
    $locale = app()->getLocale();
    $title = $settings->getTranslatable('home.testimonials_title', $locale, __('web.home.testimonials_title'));
    $subtitle = $settings->getTranslatable('home.testimonials_subtitle', $locale, __('web.home.testimonials_subtitle'));
@endphp

<section id="testimonials" class="py-6">
    <div class="app-container">
        <div class="grid gap-10 lg:grid-cols-[1fr_1.2fr] lg:items-center">
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="h-8 w-8 rounded-full bg-brand-accent"></span>
                        <span class="h-8 w-8 rounded-full border border-border-neutral bg-white"></span>
                    </div>
                </div>
                <h2 class="text-3xl font-semibold text-neutral-text">{{ $title }}</h2>
                <p class="text-sm text-secondary">{{ $subtitle }}</p>
            </div>

            <div
                x-data="{
                    active: 0,
                    total: {{ count($testimonials) }},
                    timer: null,
                    dragStartX: null,
                    isDragging: false,
                    start() {
                        if (this.total < 2) return;
                        this.timer = setInterval(() => {
                            this.active = (this.active + 1) % this.total;
                        }, 7000);
                    },
                    goTo(index) {
                        this.active = index;
                        if (this.timer) {
                            clearInterval(this.timer);
                            this.start();
                        }
                    },
                    onDragStart(e) {
                        this.dragStartX = e.touches ? e.touches[0].clientX : e.clientX;
                        this.isDragging = true;
                    },
                    onDragEnd(e) {
                        if (!this.isDragging || this.dragStartX === null) return;
                        const endX = e.changedTouches ? e.changedTouches[0].clientX : e.clientX;
                        const diff = this.dragStartX - endX;
                        if (Math.abs(diff) > 50) {
                            diff > 0
                                ? this.goTo((this.active + 1) % this.total)
                                : this.goTo((this.active - 1 + this.total) % this.total);
                        }
                        this.isDragging = false;
                        this.dragStartX = null;
                    }
                }"
                x-init="start()"
                @mousedown="onDragStart"
                @mouseup="onDragEnd"
                @mouseleave="isDragging = false; dragStartX = null"
                @touchstart.passive="onDragStart"
                @touchend="onDragEnd"
                class="relative cursor-grab active:cursor-grabbing select-none"
            >
                <div class="relative">
                    @foreach ($testimonials as $index => $testimonial)
                        <div
                            class="rounded-2xl border border-border-neutral bg-white p-8 shadow-card transition-opacity duration-500"
                            x-show="active === {{ $index }}"
                            x-cloak
                        >
                            <div class="flex items-center gap-1 text-[#FFD05B]">
                                @for ($i = 0; $i < 5; $i++)
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.2 3.7a1 1 0 00.95.69h3.889c.969 0 1.372 1.24.588 1.81l-3.146 2.286a1 1 0 00-.364 1.118l1.2 3.7c.3.921-.755 1.688-1.54 1.118L10 13.347l-3.128 2.284c-.784.57-1.838-.197-1.539-1.118l1.2-3.7a1 1 0 00-.364-1.118L3.023 9.127c-.784-.57-.38-1.81.588-1.81h3.889a1 1 0 00.95-.69l1.2-3.7z" />
                                    </svg>
                                @endfor
                            </div>
                            <p class="mt-4 text-sm text-secondary">"{{ $testimonial['quote'] ?? '' }}"</p>
                            <div class="mt-6 flex items-center gap-3">
                                <img class="h-12 w-12 rounded-full object-cover" src="{{ $testimonial['avatar'] ?? 'https://themiran.net/wp-content/uploads/2025/07/picture_2025-07-10_at_3.03.48_PM.png' }}" alt="{{ $testimonial['name'] ?? '' }}">
                                <div>
                                    <div class="text-sm font-semibold text-neutral-text">{{ $testimonial['name'] ?? '' }}</div>
                                    <div class="text-xs text-muted">{{ $testimonial['role'] ?? '' }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if (count($testimonials) > 1)
                    <div class="mt-6 flex items-center justify-center gap-4">
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-border-neutral bg-white text-secondary transition hover:bg-brand-accent hover:text-white hover:border-brand-accent"
                            x-on:click="goTo((active - 1 + total) % total)"
                            aria-label="Previous"
                        >
                            <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10 12L6 8l4-4"/>
                            </svg>
                        </button>

                        <div class="flex items-center gap-2">
                            @foreach ($testimonials as $index => $testimonial)
                                <button
                                    type="button"
                                    class="h-2.5 rounded-full border border-border-neutral transition-all duration-300"
                                    :class="active === {{ $index }} ? 'w-6 bg-brand-accent border-brand-accent' : 'w-2.5 bg-white'"
                                    x-on:click="goTo({{ $index }})"
                                    aria-label="Go to testimonial {{ $index + 1 }}"
                                ></button>
                            @endforeach
                        </div>

                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-full border border-border-neutral bg-white text-secondary transition hover:bg-brand-accent hover:text-white hover:border-brand-accent"
                            x-on:click="goTo((active + 1) % total)"
                            aria-label="Next"
                        >
                            <svg class="h-4 w-4 rtl:rotate-180" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 12l4-4-4-4"/>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
