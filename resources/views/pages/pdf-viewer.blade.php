<x-layouts.app :title="$title">
    <section class="app-container py-10">
        <h1 class="text-2xl font-bold text-[#100f14] mb-6 text-center">{{ $title }}</h1>

        <div class="mx-auto w-full max-w-4xl" oncontextmenu="return false;">
            <iframe
                src="{{ $fileUrl }}#toolbar=0&navpanes=0&scrollbar=0"
                title="{{ $title }}"
                class="h-[80vh] w-full rounded-2xl border border-gray-200 shadow-md"
            ></iframe>
        </div>
    </section>

    <section class="app-container pb-10 text-center">
        <div class="mx-auto max-w-xs">
            <x-course-profile-cta :cta-label="$ctaLabel ?? null" :cta-url="$ctaUrl ?? null" />
        </div>
    </section>
</x-layouts.app>