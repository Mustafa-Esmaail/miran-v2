<x-layouts.app :meta-title="$metaTitle ?? null" :meta-description="$metaDescription ?? null" :og-image-url="$ogImageUrl ?? null">
    <div class="{{ in_array($page->slug, ['contact', 'corporate', 'complaints', 'about'], true) ? '' : '' }}">
        @if (!empty($sections) && $sections->count() > 0)
            @foreach ($sections as $section)
                @includeIf("sections.{$section->type}", ['section' => $section])
            @endforeach
        @else
            <article class="rounded-xl bg-surface p-6 shadow-sm">
                <h1 class="text-2xl font-semibold text-primary">{{ $page->title }}</h1>
                <div class="rich-content mt-4 max-w-none">
                    {!! $page->body !!}
                </div>
            </article>
        @endif
    </div>
</x-layouts.app>
