@props(['item', 'imageUrl' => null])

<article class="flex items-center justify-between gap-6  p-6">
    <div class="flex min-w-0 flex-1 flex-col gap-4">
        <div class="flex items-center gap-2">
            <span
                class="rounded-2xl border px-3 py-1  border-[#d8d1e2]  text-base font-semibold text-[#260e3f]">{{ $item->categoryName }}</span>
        </div>

        <div class="flex flex-col gap-3">
            <a href="{{ route('blog.show', $item->slug) }}"
                class="line-clamp-2 text-2xl font-bold text-[#151318] transition hover:text-[#99745c]">
                {{ $item->title }}
            </a>
            <p class="line-clamp-3 text-base text-[#322f37]">
                {{ \Illuminate\Support\Str::limit($item->excerpt, 140) }}
            </p>
        </div>

        @if (!$item->isDraft && $item->publishedAt)
            <p class="text-sm font-medium text-[#8a8394]">
                {{ $item->publishedAt }}
            </p>
        @endif
    </div>

    <img src="{{ $imageUrl }}" alt="{{ $item->title }}"
        class="h-[181px] w-[180px] flex-shrink-0 rounded-2xl object-cover" loading="lazy">
</article>
