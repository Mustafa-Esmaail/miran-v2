@php
    $locale = app()->getLocale();
    $getText = fn (string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn (string $key, $default = null) => $section->fieldValue($key, $default);
    $items = $getValue('items', []);
    $items = is_array($items) ? $items : [];
@endphp

<section id="testimonials" class="section-shell">
    <div class="section-title">
        <h2>{{ $getText('title', $section->title ?? __('web.home.testimonials_title')) }}</h2>
        <p>{{ $getText('subtitle', $section->subtitle ?? __('web.home.testimonials_subtitle')) }}</p>
    </div>

    <div
        x-data="{
            active: 0,
            total: {{ count($items) }},
            timer: null,
            start() {
                if (this.total < 2) {
                    return;
                }
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
            }
        }"
        x-init="start()"
        class="mt-6"
    >
        <div class="relative">
            @foreach ($items as $index => $testimonial)
                @php
                    $quote = $testimonial["quote_{$locale}"]
                        ?? $testimonial["text_{$locale}"]
                        ?? $testimonial['quote_en']
                        ?? $testimonial['text_en']
                        ?? $testimonial['quote_ar']
                        ?? $testimonial['text_ar']
                        ?? $testimonial['quote']
                        ?? $testimonial['text']
                        ?? '';
                    $name = $testimonial["name_{$locale}"] ?? $testimonial['name_en'] ?? $testimonial['name_ar'] ?? $testimonial['name'] ?? '';
                    $role = $testimonial["role_{$locale}"] ?? $testimonial['role_en'] ?? $testimonial['role_ar'] ?? $testimonial['role'] ?? '';
                    $avatar = $testimonial['avatar'] ?? null;
                    $avatar = $avatar ? (str_starts_with($avatar, 'http') ? $avatar : \App\Support\S3Url::temporary($avatar)) : null;
                @endphp
                <div
                    class="card p-6 transition-opacity duration-500"
                    x-show="active === {{ $index }}"
                    x-cloak
                >
                    <div class="flex items-center gap-1 text-warning">
                        @for ($i = 0; $i < 5; $i++)
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.2 3.7a1 1 0 00.95.69h3.889c.969 0 1.372 1.24.588 1.81l-3.146 2.286a1 1 0 00-.364 1.118l1.2 3.7c.3.921-.755 1.688-1.54 1.118L10 13.347l-3.128 2.284c-.784.57-1.838-.197-1.539-1.118l1.2-3.7a1 1 0 00-.364-1.118L3.023 9.127c-.784-.57-.38-1.81.588-1.81h3.889a1 1 0 00.95-.69l1.2-3.7z" />
                            </svg>
                        @endfor
                    </div>
                    <p class="mt-4 text-sm text-secondary">"{{ $quote }}"</p>
                    <div class="mt-5 flex items-center gap-3">
                        <img class="h-10 w-10 rounded-full object-cover" src="{{ $avatar ?? 'https://themiran.net/wp-content/uploads/2025/07/picture_2025-07-10_at_3.03.48_PM.png' }}" alt="{{ $name }}">
                        <div>
                            <div class="text-sm font-semibold text-primary">{{ $name }}</div>
                            <div class="text-xs text-muted">{{ $role }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if (count($items) > 1)
            <div class="mt-4 flex items-center justify-center gap-2">
                @foreach ($items as $index => $testimonial)
                    <button
                        type="button"
                        class="h-2.5 w-2.5 rounded-full border border-default"
                        :class="active === {{ $index }} ? 'bg-primary' : 'bg-muted'"
                        x-on:click="goTo({{ $index }})"
                        aria-label="Go to testimonial {{ $index + 1 }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
</section>







