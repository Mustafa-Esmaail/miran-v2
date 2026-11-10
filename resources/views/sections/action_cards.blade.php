@php
    $locale = app()->getLocale();
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);
    $items = $getValue('items', []);
    $items = is_array($items) ? $items : [];
@endphp

<section id="action-cards" class="py-6">
    <div class="app-container">
        <div class="flex flex-col items-center gap-6 rounded-3xl bg-white px-3 py-6 shadow-card sm:px-10 lg:px-[72px]">
            <div class="grid w-full grid-cols-3 gap-2 sm:gap-4 lg:gap-6">
                @foreach ($items as $item)
                    @php
                        $text = $item["text_{$locale}"] ?? ($item['text_en'] ?? ($item['text_ar'] ?? ''));
                        $url = $item['url'] ?? null;
                        $icon = $item['icon'] ?? null;
                        $wrapperClasses =
                            'group relative flex h-full flex-col justify-center items-center gap-1 rounded-2xl border border-[#99745c] bg-white p-2 lg:p-6 text-center shadow-[0px_2px_9px_0_rgba(0,0,0,0.07)]';
                    @endphp

                    @if ($url)
                        <a href="{{ $url }}" class="{{ $wrapperClasses }}">
                    @else
                        <div class="{{ $wrapperClasses }}">
                    @endif
                        <span class="flex h-7 w-7 items-center justify-center">
                            @if ($icon)
                                {!! $icon !!}
                            @else
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M12 2l3.3 6.6 7.3 1.1-5.3 5.2 1.2 7.1L12 18.6 5.5 22l1.2-7.1-5.3-5.2 7.3-1.1L12 2z" />
                                </svg>
                            @endif
                        </span>
                        <p class="lg:text-lg text-sm font-semibold text-[#242528]">{!! nl2br(e($text)) !!}</p>
                    @if ($url)
                        </a>
                    @else
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</section>
