<section id="courses" class="py-6">
    <div class="app-container">
        {{-- Featured courses header, centered like Figma section --}}
        <div class="flex flex-col items-center gap-4 text-center">
            <h2 class="text-[28px] font-bold text-neutral-text sm:text-[32px]">
                {{ $vm->title }}
            </h2>
            <p class="max-w-2xl text-sm text-neutral-muted sm:text-base">
                {{ $vm->subtitle }}
            </p>
        </div>
        {{--
        <div class="mt-8 flex justify-center">
            <div class="flex w-full max-w-5xl flex-wrap items-center justify-between gap-2 rounded-[100px] bg-white p-3"
                style="box-shadow: 30px 0px 90px 0 rgba(113,150,147,0.1);">
                <button type="button" wire:click="$set('categoryId', null)"
                    @class([
                        'flex items-start gap-2.5 rounded-[200px] px-3 py-2 text-sm font-bold transition',
                        'bg-[#99745c] text-white' => $categoryId === null,
                        'bg-white text-[#afafaf]' => $categoryId !== null,
                    ])>
                    {{ __('web.filters.all') }}
                </button>
                @foreach ($filterCategories as $category)
                    <button type="button" wire:click="$set('categoryId', {{ $category->id }})"
                        @class([
                            'flex items-start gap-2.5 rounded-[200px] px-3 py-2 text-sm font-bold transition',
                            'bg-[#99745c] text-white' => $categoryId === $category->id,
                            'bg-white text-[#afafaf]' => $categoryId !== $category->id,
                        ])>
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div> --}}

        <div x-data="miranSlider(3, 4000)" class="mt-10">
            <div class="overflow-hidden">
                <div x-ref="track" class="flex gap-6 transition-[transform] duration-500 ease-in-out">
                    @forelse ($cards as $card)
                        <x-course-card :card="$card" />
                    @empty
                        <div class="rounded-2xl border border-border-neutral bg-white p-6 text-center text-sm text-secondary">
                            {{ __('web.courses.empty') }}
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="mt-4 flex justify-center gap-2">
                <template x-for="dot in _dots" :key="dot">
                    <button @click="active = dot; _apply(); _resetTimer()"
                        class="h-2 rounded-full bg-[#99745c] transition-all duration-300"
                        :class="_page === dot ? 'w-6 opacity-100' : 'w-2 opacity-30'">
                    </button>
                </template>
            </div>
        </div>

        <div class="mt-10 flex justify-center">
            <a href="{{ $buttonUrl }}"
                class="flex h-[52px] items-center gap-2 rounded-[32px] bg-[#99745c] px-6 py-3 text-lg font-medium text-white">
                <span>{{ $buttonLabel }}</span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6">
                    <path
                        d="M21.25 11.9999C21.25 12.1899 21.18 12.3799 21.03 12.5299L14.96 18.5999C14.67 18.8899 14.19 18.8899 13.9 18.5999C13.61 18.3099 13.61 17.8299 13.9 17.5399L19.44 11.9999L13.9 6.45988C13.61 6.16988 13.61 5.68988 13.9 5.39988C14.19 5.10988 14.67 5.10988 14.96 5.39988L21.03 11.4699C21.18 11.6199 21.25 11.8099 21.25 11.9999Z"
                        fill="white" />
                    <path
                        d="M21.08 12C21.08 12.41 20.74 12.75 20.33 12.75L3.5 12.75C3.09 12.75 2.75 12.41 2.75 12C2.75 11.59 3.09 11.25 3.5 11.25L20.33 11.25C20.74 11.25 21.08 11.59 21.08 12Z"
                        fill="white" />
                </svg>
            </a>
        </div>
    </div>
</section>
