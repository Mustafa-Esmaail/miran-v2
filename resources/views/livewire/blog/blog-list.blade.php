@php
    use Illuminate\Support\Str;

    $fallbackImage = asset('img/hero-home.png');
@endphp

<div class=" text-neutral-text">
    <section class="relative overflow-hidden bg-brand-dark text-white">
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]"
            style="background-image: linear-gradient(rgba(255,255,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 120px 120px;">
        </div>
        <div class="pointer-events-none absolute -top-[420px] end-[-380px] h-[760px] w-[760px] rounded-full blur-2xl"
            style="background: radial-gradient(circle, rgba(113,150,147,0.4), rgba(113,150,147,0));"></div>

        <div class="app-container">
            <div class="flex flex-col items-center gap-6 py-16 text-center sm:py-20 lg:py-24">
                <h1 class="text-4xl font-semibold text-[#f5f5f6] sm:text-5xl">{{ __('web.blog.title') }}</h1>
                <p class="max-w-2xl text-base text-white/80 sm:text-lg">{{ __('web.blog.subtitle') }}</p>

                <div class="flex w-full max-w-3xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-center">
                    <div
                        class="flex flex-1 items-center gap-3 rounded-full bg-white/95 px-6 py-3 text-[#82868e] shadow-sm">
                        <x-icon.search class="h-5 w-5 text-[#82868e]" />
                        <input type="search" wire:model.live.debounce.400ms="search"
                            placeholder="{{ __('web.blog.search_placeholder') }}"
                            class="w-full border-0 bg-transparent p-0 text-base text-[#242528] placeholder:text-[#82868e] focus:ring-0">
                    </div>

                    <div class="relative">
                        <select wire:model.live="categoryId"
                            class="appearance-none rounded-full bg-[#99745c] px-6 py-3 pe-12 text-base text-white focus:ring-0">
                            <option value="">{{ __('web.blog.all_categories') }}</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->title }}</option>
                            @endforeach
                        </select>
                        <x-icon.chevron-down
                            class="pointer-events-none absolute end-4 top-1/2 h-5 w-5 -translate-y-1/2 text-white" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="app-container py-10 lg:py-12">
        @if ($categorySections->isNotEmpty())
            <div class="flex flex-col gap-12">
                @foreach ($categorySections as $section)
                    <div class="flex flex-col gap-8">
                        <h2 class="text-3xl font-bold text-[#09080d] ">

                            {{ $section['category']->title }}
                        </h2>
                        <div class="grid
                            gap-6 lg:grid-cols-2">
                            @foreach ($section['items'] as $item)
                                <x-blog-card :item="$item" :image-url="$item->imageUrl ?? $fallbackImage" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-2">
                @forelse ($items as $item)
                    <x-blog-card :item="$item" :image-url="$item->imageUrl ?? $fallbackImage" />
                @empty
                    <div
                        class="col-span-full rounded-2xl border border-dashed border-[#ced0d3] bg-white p-8 text-center text-sm text-[#71757a]">
                        {{ __('web.blog.empty') }}
                    </div>
                @endforelse
            </div>

            @if ($paginator)
                <div class="mt-10">
                    {{ $paginator->links('components.pagination.courses') }}
                </div>
            @endif
        @endif
    </section>
</div>
