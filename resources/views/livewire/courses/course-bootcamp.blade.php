{{-- Livewire needs ONE root element --}}
<div class="pb-20 overflow-x-hidden camp-page bg-camp-cream font-camp-body text-camp-dark lg:pb-0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;500;600;700;800;900&family=Readex+Pro:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    @php
        $content = $bootcamp->content;
        $cohort = $bootcamp->cohort;
        $stats = $bootcamp->stats;
        $arrowClass = $isRtl ? 'rotate-180' : '';
        $currencyLabel = __('web.common.currency.sar');
        $canBook = $cohort['is_available'] ?? false;
        $priceText = $course->isFree
            ? __('web.courses.free')
            : ($displayPrice !== null
                ? number_format($displayPrice)
                : null);
        $stepStyles = [
            'bg-camp-dark text-camp-sand',
            'bg-camp-sand text-camp-dark',
            'bg-camp-dark text-camp-sand',
            'bg-camp-green text-white',
        ];
    @endphp

    {{-- ==================== HERO (same as course-show) ==================== --}}
    <section id="about" class="relative text-white camp-site-font bg-brand-dark">
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]"
            style="background-image: linear-gradient(rgba(255,255,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 120px 120px;">
        </div>

        <div
            class="relative flex flex-col items-start gap-4 px-3 pb-0 pt-4 sm:gap-8 sm:px-6 sm:pt-10 lg:px-[68px] lg:pt-12">
            <div class="flex flex-col w-full gap-4 lg:flex-row lg:items-start lg:justify-between lg:gap-8">
                <div class="flex flex-col gap-3 sm:gap-6">
                    <div class="flex flex-col gap-1 sm:gap-2">
                        <h1
                            class="text-2xl font-semibold text-[#f5f5f6] sm:text-3xl lg:text-4xl {{ $isRtl ? 'text-right' : 'text-left' }}">
                            {{ $course->title }}
                        </h1>
                    </div>


                    <div class="flex flex-wrap gap-2 sm:gap-4">

                    </div>
                </div>

            </div>

            <div class="grid items-start w-full gap-4 pb-3 sm:gap-8 sm:pb-10 lg:grid-cols-12 lg:pb-12">
                <div
                    class="relative h-full w-full overflow-hidden rounded-3xl bg-black/20 sm:h-80 lg:col-span-8 lg:h-[479px]">
                    @if ($course->introVideoUrl)
                        <video class="object-cover w-full h-full" controls playsinline preload="metadata"
                            poster="{{ $course->imageUrl }}">
                            <source src="{{ $course->introVideoUrl }}" type="video/mp4">
                        </video>
                    @elseif ($course->imageUrl)
                        <img src="{{ $course->imageUrl }}" alt="{{ $course->title }}"
                            class="object-cover w-full h-full">
                    @endif

                    @if ($course->introVideoUrl)
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div
                                class="flex items-center gap-2 rounded-[100px] border border-[#99745c] bg-[#3d3d3d]/[0.04] p-2.5 backdrop-blur-2xl sm:p-4">
                                <svg width="72" height="72" viewBox="0 0 72 72" fill="none"
                                    class="h-[72px] w-[72px]">
                                    <path
                                        d="M36 6C19.44 6 6 19.44 6 36C6 52.56 19.44 66 36 66C52.56 66 66 52.56 66 36C66 19.44 52.56 6 36 6ZM30 49.5V22.5L48 36L30 49.5Z"
                                        fill="white"></path>
                                </svg>
                            </div>
                        </div>
                    @endif
                </div>

                <aside id="book" class="scroll-mt-28 lg:col-span-4">

                    <div
                        class="flex flex-col gap-3 rounded-3xl border border-[#ced0d3] bg-white p-3 sm:gap-6 sm:p-8 lg:p-10">
                        <div class="flex flex-col gap-3 sm:gap-6">

                        </div>

                        <div class="flex flex-col gap-3 sm:gap-6">

                            <div class="flex items-center gap-2">
                                {{-- <div
                                    class="flex items-center gap-2 rounded-3xl border border-[#ced0d3] bg-white px-4 py-2">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                        class="w-5 h-5">
                                        <path
                                            d="M14.4297 9.61154L12.9597 4.77154C12.6697 3.82154 11.3297 3.82154 11.0497 4.77154L9.56971 9.61154H5.11971C4.14971 9.61154 3.74971 10.8615 4.53971 11.4215L8.17972 14.0215L6.74971 18.6315C6.45971 19.5615 7.53972 20.3115 8.30972 19.7215L11.9997 16.9215L15.6897 19.7315C16.4597 20.3215 17.5397 19.5715 17.2497 18.6415L15.8197 14.0315L19.4597 11.4315C20.2497 10.8615 19.8497 9.62154 18.8797 9.62154H14.4297V9.61154Z"
                                            fill="#FFB127"></path>
                                    </svg>
                                    <p class="text-sm font-semibold text-[#242528]">
                                        {{ number_format($course->ratingAverage ?? 0, 1) }}
                                    </p>
                                </div> --}}

                                <button type="button"
                                    class="flex items-center justify-center rounded-3xl border border-[#ced0d3] bg-white p-2"
                                    wire:click="toggleCourseWishlist"
                                    aria-pressed="{{ $isWishlisted ? 'true' : 'false' }}">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                        class="w-5 h-5">
                                        @if ($isWishlisted)
                                            <path
                                                d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"
                                                fill="#99745c"></path>
                                        @else
                                            <path fill-rule="evenodd" clip-rule="evenodd"
                                                d="M5.62436 4.4242C3.96537 5.18252 2.75 6.98623 2.75 9.1371C2.75 11.3345 3.64922 13.0282 4.93829 14.4798C6.00072 15.6761 7.28684 16.6676 8.54113 17.6346C8.83904 17.8643 9.13515 18.0926 9.42605 18.3219C9.95208 18.7366 10.4213 19.1005 10.8736 19.3648C11.3261 19.6293 11.6904 19.75 12 19.75C12.3096 19.75 12.6739 19.6293 13.1264 19.3648C13.5787 19.1005 14.0479 18.7366 14.574 18.3219C14.8649 18.0926 15.161 17.8643 15.4589 17.6346C16.7132 16.6676 17.9993 15.6761 19.0617 14.4798C20.3508 13.0282 21.25 11.3345 21.25 9.1371C21.25 6.98623 20.0346 5.18252 18.3756 4.4242C16.7639 3.68748 14.5983 3.88258 12.5404 6.02074C12.399 6.16763 12.2039 6.25064 12 6.25064C11.7961 6.25064 11.601 6.16763 11.4596 6.02074C9.40166 3.88258 7.23607 3.68748 5.62436 4.4242ZM12 4.45882C9.68795 2.39024 7.09896 2.10087 5.00076 3.05996C2.78471 4.07293 1.25 6.42503 1.25 9.1371C1.25 11.8026 2.3605 13.8361 3.81672 15.4758C4.98287 16.7889 6.41022 17.888 7.67083 18.8586C7.95659 19.0786 8.23378 19.292 8.49742 19.4999C9.00965 19.9037 9.55954 20.3343 10.1168 20.6599C10.6739 20.9854 11.3096 21.25 12 21.25C12.6904 21.25 13.3261 20.9854 13.8832 20.6599C14.4405 20.3343 14.9903 19.9037 15.5026 19.4999C15.7662 19.292 16.0434 19.0786 16.3292 18.8586C17.5898 17.888 19.0171 16.7889 20.1833 15.4758C21.6395 13.8361 22.75 11.8026 22.75 9.1371C22.75 6.42503 21.2153 4.07293 18.9992 3.05996C16.901 2.10087 14.3121 2.39024 12 4.45882Z"
                                                fill="#99745c"></path>
                                        @endif
                                    </svg>
                                </button>

                                <button type="button"
                                    class="flex items-center justify-center rounded-3xl border border-[#ced0d3] bg-white p-2"
                                    data-copy-url="{{ $shareUrl }}"
                                    data-feedback="{{ __('web.course.link_copied') }}">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                        class="w-5 h-5">
                                        <path
                                            d="M18 16.12C17.24 16.12 16.56 16.42 16.04 16.89L8.91 12.74C8.96 12.51 9 12.28 9 12.04C9 11.8 8.96 11.57 8.91 11.34L15.96 7.23C16.5 7.73 17.21 8.04 18 8.04C19.66 8.04 21 6.7 21 5.04C21 3.38 19.66 2.04 18 2.04C16.34 2.04 15 3.38 15 5.04C15 5.28 15.04 5.51 15.09 5.74L8.04 9.85C7.5 9.35 6.79 9.04 6 9.04C4.34 9.04 3 10.38 3 12.04C3 13.7 4.34 15.04 6 15.04C6.79 15.04 7.5 14.73 8.04 14.23L15.16 18.39C15.11 18.6 15.08 18.82 15.08 19.04C15.08 20.65 16.39 21.96 18 21.96C19.61 21.96 20.92 20.65 20.92 19.04C20.92 17.43 19.61 16.12 18 16.12ZM18 4.04C18.55 4.04 19 4.49 19 5.04C19 5.59 18.55 6.04 18 6.04C17.45 6.04 17 5.59 17 5.04C17 4.49 17.45 4.04 18 4.04ZM6 13.04C5.45 13.04 5 12.59 5 12.04C5 11.49 5.45 11.04 6 11.04C6.55 11.04 7 11.49 7 12.04C7 12.59 6.55 13.04 6 13.04ZM18 20.06C17.45 20.06 17 19.61 17 19.06C17 18.51 17.45 18.06 18 18.06C18.55 18.06 19 18.51 19 19.06C19 19.61 18.55 20.06 18 20.06Z"
                                            fill="#99745c"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="flex items-end gap-[5px]">
                                <svg width="23" height="26" viewBox="0 0 23 26" fill="none"
                                    class="h-[26px] w-[23px]">
                                    <path
                                        d="M14.314 23.0335C13.9036 23.9539 13.6323 24.9528 13.5283 26.0004L22.2138 24.1329C22.6243 23.2127 22.8954 22.2136 22.9995 21.166L14.314 23.0335Z"
                                        fill="#99745C"></path>
                                    <path
                                        d="M22.2134 18.5379C22.6238 17.6176 22.8951 16.6185 22.999 15.5709L16.2333 17.0264V14.2285L22.2132 12.9432C22.6236 12.0229 22.8949 11.0238 22.9988 9.97626L16.2331 11.4304V1.36851C15.1964 1.95726 14.2757 2.74095 13.5272 3.66536V12.0124L10.8214 12.5941V0C9.78467 0.588543 8.86397 1.37244 8.11554 2.29685V13.1756L2.0612 14.4768C1.65077 15.3971 1.37926 16.3962 1.27512 17.4438L8.11554 15.9734V19.4968L0.784691 21.0725C0.374261 21.9928 0.102961 22.9919 -0.000976562 24.0394L7.67237 22.3901C8.29702 22.2587 8.83389 21.8852 9.18294 21.3711L10.5902 19.2609V19.2605C10.7363 19.0422 10.8214 18.779 10.8214 18.4954V15.3917L13.5272 14.81V20.4057L22.2132 18.5375L22.2134 18.5379Z"
                                        fill="#99745C"></path>
                                </svg>

                                @if ($course->isFree)
                                    <p class="text-2xl font-semibold text-[#99745c] sm:text-3xl lg:text-4xl">
                                        {{ __('web.courses.free') }}
                                    </p>
                                @elseif ($displayPrice !== null)
                                    <p class="text-2xl font-semibold text-[#99745c] sm:text-3xl lg:text-4xl">
                                        {{ number_format($displayPrice, 2) }}
                                    </p>
                                    @if ($hasDiscount)
                                        <p class="text-sm text-[#afafaf] line-through">
                                            {{ number_format($course->price, 2) }}
                                        </p>
                                    @endif
                                @else
                                    <p class="text-lg text-[#4b4c53]">{{ __('web.courses.price_tbd') }}</p>
                                @endif
                            </div>

                            <x-tamara-widget :amount="$displayPrice" :currency="$siteCurrency" :publicKey="$tamaraPublicKey" />

                            <form wire:submit.prevent="addCourseToCart" class="space-y-2.5 sm:space-y-4">
                                @if ($course->accreditationNumber)
                                    <p class="text-sm font-semibold text-[#242528]">
                                        {{ __('web.course.accreditation_number') }}:
                                        {{ $course->accreditationNumber }}
                                    </p>
                                @endif

                                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                                    <input type="number" min="1" wire:model.live="quantity"
                                        class="col-span-1 w-full rounded-3xl border border-[#ced0d3] px-3 py-2.5 text-base sm:px-6 sm:py-3"
                                        placeholder="{{ __('web.course.quantity') }}">
                                    <select wire:model="courseGroupId"
                                        class="col-span-2 w-full rounded-3xl border border-[#ced0d3] px-3 py-2.5 text-base sm:px-6 sm:py-3">
                                        <option value="">{{ __('web.course.select_group') }}</option>
                                        @foreach ($course->groups as $group)
                                            <option value="{{ $group['id'] }}">{{ $group['title'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @error('courseGroupId')
                                    <p class="text-xs text-alert">{{ $message }}</p>
                                @enderror

                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-3">
                                    <button type="button" wire:click="buyNow"
                                        class="w-full rounded-3xl bg-[#99745c] px-3 py-2.5 text-lg sm:px-6 sm:py-3 font-semibold text-white transition hover:bg-[#86634e] hover:shadow-[0_12px_30px_rgba(153,116,92,0.22)]"
                                        wire:loading.attr="disabled">
                                        {{ __('web.course.buy_now') }}
                                    </button>
                                    <button type="submit"
                                        class="w-full rounded-3xl border border-[#99745c] px-3 py-2.5 text-lg sm:px-6 sm:py-3 font-semibold text-[#99745c] transition hover:bg-[#99745c] hover:text-white hover:shadow-[0_12px_30px_rgba(153,116,92,0.18)]"
                                        wire:loading.attr="disabled">
                                        {{ __('web.course.add_to_cart') }}
                                    </button>
                                </div>
                            </form>
                        </div>


                        <div class="h-px w-full bg-[#d1d1d1]">

                        </div>

                        <div class="flex flex-col gap-2 sm:gap-3">
                            <p class="text-sm font-semibold text-[#242528]">{{ __('web.course.payment_methods') }}</p>
                            <div class="flex flex-wrap items-center gap-2.5">
                                @foreach ($paymentMethods as $method)
                                    <span
                                        class="inline-flex items-center rounded-full border border-[#ced0d3] bg-[#f8f8f8] px-3 py-1.5 text-xs font-semibold text-[#4b4c53]">
                                        @if (!empty($method['logo']))
                                            <img src="{{ $method['logo'] }}" alt="{{ $method['label'] }}"
                                                class="h-8 w-auto max-w-[80px] object-contain">
                                        @else
                                            {{ $method['label'] }}
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        </div>

                    </div>
                </aside>
            </div>
        </div>
    </section>

    {{-- ==================== INDUSTRIES MARQUEE ==================== --}}
    @if ($content['industries'] !== [])
        <section class="relative overflow-hidden border-y border-white/10 bg-camp-darker py-2.5 sm:py-6"
            aria-hidden="true">
            <div class="flex w-max animate-camp-marquee">
                @foreach ([1, 2] as $copy)
                    <div
                        class="flex gap-3 text-xs font-medium shrink-0 whitespace-nowrap pe-6 text-white/80 sm:gap-4 sm:pe-8 sm:text-sm">
                        @foreach ($content['industries'] as $industry)
                            <span class="inline-flex items-center gap-2"><span
                                    class="h-1.5 w-1.5 rounded-full bg-camp-sand"></span>{{ $industry }}</span>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ==================== PILLARS ==================== --}}
    @if ($content['pillars'] !== [])
        <section class="relative py-4 bg-camp-cream sm:py-8">
            <div class="px-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-2.5 max-w-3xl text-start sm:mb-5">
                    <span
                        class="block mb-1 text-xs font-bold tracking-wide uppercase text-camp-sand-dark">{{ __('web.bootcamp.pillars.eyebrow') }}</span>
                    <h2 class="mb-1 text-2xl font-black sm:text-4xl">{{ __('web.bootcamp.pillars.title') }}</h2>
                    <p class="text-sm leading-relaxed text-camp-muted sm:text-base">
                        {{ __('web.bootcamp.pillars.subtitle') }}</p>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-4 lg:grid-cols-4">
                    @foreach ($content['pillars'] as $pillar)
                        @php $isLast = $loop->last && $loop->count > 1; @endphp
                        <div
                            class="flex flex-col justify-between rounded-3xl border p-3 sm:p-5 {{ $isLast ? 'border-camp-darker bg-camp-dark text-white shadow-xl' : 'border-camp-border bg-white shadow-sm' }}">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span
                                        class="text-2xl font-black font-camp-heading text-camp-sand sm:text-3xl">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    @if ($pillar['tag'])
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $isLast ? 'bg-white/10 text-camp-sand' : 'bg-camp-sand-light text-camp-sand-dark' }}">{{ $pillar['tag'] }}</span>
                                    @endif
                                </div>
                                <h3 class="mb-1 text-lg font-bold sm:text-xl">{{ $pillar['title'] }}</h3>
                                @if ($pillar['description'])
                                    <p
                                        class="text-xs leading-relaxed sm:text-sm {{ $isLast ? 'text-white/80' : 'text-camp-muted' }}">
                                        {{ $pillar['description'] }}</p>
                                @endif
                            </div>
                            @if ($pillar['footer'])
                                <div
                                    class="mt-3 border-t pt-3.5 text-xs font-semibold {{ $isLast ? 'border-white/10 text-camp-sand' : 'border-camp-border/60' }}">
                                    {{ $pillar['footer'] }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== METHOD ==================== --}}
    @if ($content['method_steps'] !== [])
        <section id="method" class="py-4 bg-white border-t border-camp-border sm:py-8">
            <div class="px-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mx-auto mb-2.5 max-w-3xl text-center sm:mb-5">
                    <span
                        class="block mb-1 text-xs font-bold tracking-wide uppercase text-camp-sand-dark">{{ __('web.bootcamp.method.eyebrow') }}</span>
                    <h2 class="mb-1 text-2xl font-black sm:text-4xl">{{ __('web.bootcamp.method.title') }}</h2>
                    <p class="text-xs text-camp-muted sm:text-base">{{ __('web.bootcamp.method.subtitle') }}</p>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:gap-4 md:grid-cols-2 lg:grid-cols-4">
                    @foreach ($content['method_steps'] as $step)
                        <div class="p-3 border rounded-3xl border-camp-border bg-camp-cream/60 sm:p-4">
                            <div
                                class="mb-2 flex h-10 w-10 items-center justify-center rounded-2xl font-camp-heading text-base font-bold shadow-md sm:h-12 sm:w-12 sm:text-lg {{ $stepStyles[$loop->index % 4] }}">
                                {{ $loop->iteration }}</div>
                            <span
                                class="mb-1 block text-xs font-semibold {{ $loop->last ? 'text-camp-green' : 'text-camp-sand-dark' }}">{{ __('web.bootcamp.method.step', ['number' => $loop->iteration]) }}</span>
                            <h3 class="mb-1 text-base font-bold sm:text-lg">{{ $step['title'] }}</h3>
                            @if ($step['description'])
                                <p class="text-xs leading-relaxed text-camp-muted">{{ $step['description'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($content['method_note'])
                    <div
                        class="mt-2.5 flex items-center gap-3 rounded-2xl border border-camp-sand/40 bg-camp-sand-light/50 p-3 sm:mt-5 sm:p-5">
                        <span
                            class="flex items-center justify-center w-8 h-8 shrink-0 rounded-xl bg-camp-sand text-camp-dark sm:h-10 sm:w-10">
                            <svg class="w-4 h-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <span class="text-xs font-medium sm:text-sm">{{ $content['method_note'] }}</span>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ==================== CURRICULUM ==================== --}}
    @if ($bootcamp->curriculumWeeks !== [])
        <section id="curriculum" class="relative py-4 bg-camp-cream sm:py-10" x-data="{ week: {{ $bootcamp->curriculumWeeks[0]['week'] }} }">
            <div class="px-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col justify-between gap-3 mb-3 sm:mb-5 lg:flex-row lg:items-end">
                    <div class="max-w-2xl text-start">
                        <span
                            class="block mb-1 text-xs font-bold tracking-wide uppercase text-camp-sand-dark">{{ __('web.bootcamp.curriculum.eyebrow') }}</span>
                        <h2 class="mb-1 text-2xl font-black sm:text-4xl">
                            {{ trans_choice('web.bootcamp.curriculum.title', $stats['modules'], ['count' => $stats['modules']]) }}
                        </h2>
                        <p class="text-xs leading-relaxed text-camp-muted sm:text-base">
                            {{ __('web.bootcamp.curriculum.subtitle') }}</p>
                    </div>

                    @if (count($bootcamp->curriculumWeeks) > 1)
                        <div class="camp-no-scrollbar flex shrink-0 items-center gap-1.5 overflow-x-auto rounded-2xl border border-camp-border bg-white p-1.5 shadow-sm"
                            role="tablist">
                            @foreach ($bootcamp->curriculumWeeks as $week)
                                <button type="button" role="tab" x-on:click="week = {{ $week['week'] }}"
                                    x-bind:aria-selected="week === {{ $week['week'] }}"
                                    x-bind:class="week === {{ $week['week'] }} ? 'bg-camp-dark text-camp-sand' :
                                        'text-camp-muted hover:text-camp-dark'"
                                    class="whitespace-nowrap rounded-xl px-3 py-2 font-camp-heading text-xs font-semibold transition-all sm:px-5 sm:py-2.5 sm:text-sm">
                                    {{ $week['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @foreach ($bootcamp->curriculumWeeks as $week)
                    <div wire:key="camp-week-{{ $week['week'] }}" x-show="week === {{ $week['week'] }}"
                        @if (!$loop->first) x-cloak @endif
                        class="grid grid-cols-1 gap-2 sm:gap-4 md:grid-cols-2">
                        @foreach ($week['modules'] as $module)
                            @if ($module['is_capstone'])
                                <div wire:key="camp-module-{{ $module['id'] }}"
                                    class="relative p-3 overflow-hidden text-white border shadow-2xl rounded-3xl border-camp-darker bg-camp-dark sm:p-6 md:col-span-2">
                                    <div
                                        class="absolute rounded-full pointer-events-none -end-24 -top-24 h-80 w-80 bg-camp-sand/20 blur-3xl">
                                    </div>
                                    <div class="relative grid items-center grid-cols-1 gap-3 sm:gap-4 lg:grid-cols-12">
                                        <div class="space-y-2 lg:col-span-7">
                                            <div
                                                class="inline-flex items-center gap-2 px-3 py-1 text-xs font-bold rounded-lg bg-camp-sand text-camp-dark">
                                                {{ __('web.bootcamp.module_number', ['number' => $module['number']]) }}
                                                · {{ $module['badge'] ?? __('web.bootcamp.curriculum.capstone') }}
                                            </div>
                                            <h3 class="text-xl font-black text-camp-sand sm:text-3xl">
                                                {{ $module['title'] }}</h3>
                                            @if ($module['description'])
                                                <div class="text-xs leading-relaxed text-white/80 sm:text-base">
                                                    {!! $module['description'] !!}</div>
                                            @endif
                                            @if ($module['bullets'] !== [])
                                                <div class="grid grid-cols-1 gap-2.5 pt-2 sm:grid-cols-3 sm:gap-3">
                                                    @foreach ($module['bullets'] as $bullet)
                                                        <div
                                                            class="p-3 text-xs font-bold border rounded-2xl border-white/10 bg-white/5 text-camp-sand">
                                                            {{ $bullet }}</div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <div
                                            class="p-3 space-y-2 text-center border rounded-2xl border-white/10 bg-white/10 sm:p-4 lg:col-span-5">
                                            @if ($module['outcome'])
                                                <span
                                                    class="block text-xs text-white/60">{{ __('web.bootcamp.curriculum.you_leave_with') }}</span>
                                                <h4 class="text-sm font-bold sm:text-base">{{ $module['outcome'] }}
                                                </h4>
                                            @endif
                                            <a href="#book"
                                                class="inline-block w-full py-3 text-xs font-bold transition-colors rounded-xl bg-camp-sand text-camp-dark hover:bg-white sm:text-sm">{{ __('web.bootcamp.book_and_start') }}</a>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div wire:key="camp-module-{{ $module['id'] }}"
                                    class="flex flex-col justify-between rounded-3xl bg-white p-3 sm:p-5 {{ $module['is_featured'] ? 'border-2 border-camp-sand shadow-md' : 'border border-camp-border shadow-sm' }}">
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <span
                                                class="rounded-lg px-2.5 py-1 text-xs font-bold {{ $module['is_featured'] ? 'bg-camp-sand text-camp-dark' : 'bg-camp-cream text-camp-muted' }}">
                                                {{ __('web.bootcamp.module_number', ['number' => $module['number']]) }}
                                                @if ($module['is_featured'])
                                                    · {{ __('web.bootcamp.curriculum.featured') }}
                                                @endif
                                            </span>
                                            @if ($module['badge'])
                                                <span
                                                    class="rounded px-2 py-0.5 text-xs font-semibold {{ $module['is_featured'] ? 'bg-camp-sand-light text-camp-sand-dark' : 'bg-camp-green/10 text-camp-green' }}">{{ $module['badge'] }}</span>
                                            @endif
                                        </div>
                                        <h3 class="mb-1 text-lg font-bold sm:text-xl">{{ $module['title'] }}</h3>
                                        @if ($module['description'])
                                            <div class="mb-3 text-xs leading-relaxed text-camp-muted sm:text-sm">
                                                {!! $module['description'] !!}</div>
                                        @endif
                                        @if ($module['bullets'] !== [])
                                            <div
                                                class="mb-3 space-y-1.5 rounded-xl bg-camp-cream p-3 text-xs text-camp-muted">
                                                @foreach ($module['bullets'] as $bullet)
                                                    <div class="flex items-center gap-2"><span
                                                            class="h-1.5 w-1.5 shrink-0 rounded-full bg-camp-sand"></span><span>{{ $bullet }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                    @if ($module['outcome'])
                                        <div
                                            class="flex items-center justify-between gap-2 rounded-2xl border border-camp-sand/30 bg-camp-sand-light/40 p-3 text-xs sm:p-3.5">
                                            <span
                                                class="font-medium shrink-0 text-camp-sand-dark">{{ __('web.bootcamp.curriculum.outcome') }}</span>
                                            <span class="font-bold truncate">{{ $module['outcome'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach

                @if ($bootcamp->syllabusUrl)
                    <div class="mt-2.5 text-center sm:mt-6">
                        <a href="{{ $bootcamp->syllabusUrl }}" target="_blank" rel="noopener"
                            class="inline-flex items-center justify-center w-full gap-2 px-5 py-3 text-xs font-semibold transition-colors bg-white border rounded-xl border-camp-border hover:text-camp-sand-dark sm:w-auto sm:text-sm">
                            <svg class="w-4 h-4 text-camp-sand-dark" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>{{ __('web.bootcamp.curriculum.download') }}</span>
                        </a>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ==================== PROJECTS ==================== --}}
    @if ($content['projects'] !== [])
        <section id="projects" class="py-4 bg-white border-t border-camp-border sm:py-8">
            <div class="px-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-2.5 max-w-2xl text-start sm:mb-5">
                    <span
                        class="block mb-1 text-xs font-bold tracking-wide uppercase text-camp-sand-dark">{{ __('web.bootcamp.projects.eyebrow') }}</span>
                    <h2 class="mb-1 text-2xl font-black sm:text-4xl">{{ __('web.bootcamp.projects.title') }}</h2>
                    <p class="text-xs leading-relaxed text-camp-muted sm:text-base">
                        {{ __('web.bootcamp.projects.subtitle') }}</p>
                </div>

                <div
                    class="flex gap-2 pb-3 overflow-x-auto snap-x snap-mandatory sm:grid sm:grid-cols-2 sm:gap-4 sm:pb-0 lg:grid-cols-3">
                    @foreach ($content['projects'] as $project)
                        <div
                            class="flex min-w-[85%] shrink-0 snap-center flex-col rounded-3xl border border-camp-border bg-camp-cream/50 p-3 sm:min-w-0 sm:shrink sm:p-5">
                            @if ($project['image'])
                                <img src="{{ $project['image'] }}" alt="{{ $project['title'] }}"
                                    class="object-cover w-full mb-2 border shadow-sm h-44 rounded-2xl border-camp-border"
                                    loading="lazy">
                            @endif
                            <div class="flex items-center gap-2 mb-1">
                                @if ($project['tag'])
                                    <span
                                        class="rounded bg-camp-green/10 px-2 py-0.5 text-[10px] font-bold text-camp-green">{{ $project['tag'] }}</span>
                                @endif
                                @if ($project['module_label'])
                                    <span
                                        class="text-[11px] font-semibold text-camp-muted">{{ $project['module_label'] }}</span>
                                @endif
                            </div>
                            <h3 class="mb-1.5 text-base font-bold sm:text-xl">{{ $project['title'] }}</h3>
                            @if ($project['description'])
                                <p class="text-xs leading-relaxed text-camp-muted">{{ $project['description'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== TRANSFORMATION ==================== --}}
    @if ($content['before_items'] !== [] || $content['after_items'] !== [])
        <section class="py-4 border-t border-camp-border bg-camp-cream sm:py-8">
            <div class="px-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mx-auto mb-2.5 max-w-2xl text-center sm:mb-5">
                    <span
                        class="block mb-1 text-xs font-bold tracking-wide uppercase text-camp-sand-dark">{{ __('web.bootcamp.transformation.eyebrow') }}</span>
                    <h2 class="mb-1 text-2xl font-black sm:text-4xl">{{ __('web.bootcamp.transformation.title') }}
                    </h2>
                    <p class="text-xs text-camp-muted sm:text-base">{{ __('web.bootcamp.transformation.subtitle') }}
                    </p>
                </div>

                <div class="grid max-w-4xl grid-cols-1 gap-3 mx-auto sm:gap-4 md:grid-cols-2">
                    @if ($content['before_items'] !== [])
                        <div class="p-3 bg-white border shadow-sm rounded-3xl border-red-200/60 sm:p-5">
                            <div
                                class="flex items-center justify-between pb-3 mb-2 border-b border-camp-border sm:mb-3 sm:pb-4">
                                <h3 class="text-base font-bold sm:text-lg">
                                    {{ __('web.bootcamp.transformation.before') }}</h3>
                                <span
                                    class="rounded-full bg-red-50 px-2.5 py-0.5 text-[11px] font-semibold text-red-700">{{ __('web.bootcamp.transformation.today') }}</span>
                            </div>
                            <ul class="space-y-2 text-xs text-camp-muted sm:space-y-2 sm:text-sm">
                                @foreach ($content['before_items'] as $item)
                                    <li class="flex items-start gap-2.5"><span
                                            class="font-bold text-red-500 shrink-0">✕</span><span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($content['after_items'] !== [])
                        <div
                            class="p-3 text-white border shadow-xl rounded-3xl border-camp-darker bg-camp-dark sm:p-5">
                            <div
                                class="flex items-center justify-between pb-3 mb-2 border-b border-white/10 sm:mb-3 sm:pb-4">
                                <h3 class="text-base font-bold text-camp-sand sm:text-lg">
                                    {{ __('web.bootcamp.transformation.after') }}</h3>
                                @if ($stats['weeks'])
                                    <span
                                        class="rounded-full bg-camp-green/20 px-2.5 py-0.5 text-[11px] font-semibold text-camp-green">{{ trans_choice('web.bootcamp.transformation.after_weeks', $stats['weeks'], ['count' => $stats['weeks']]) }}</span>
                                @endif
                            </div>
                            <ul class="space-y-2 text-xs text-white/90 sm:space-y-2 sm:text-sm">
                                @foreach ($content['after_items'] as $item)
                                    <li class="flex items-start gap-2.5"><span
                                            class="font-bold shrink-0 text-camp-green">✓</span><span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== AUDIENCE ==================== --}}
    @if ($content['audience'] !== [] || $content['not_for'])
        <section class="py-4 bg-white border-t border-camp-border sm:py-8">
            <div class="px-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mx-auto mb-2.5 max-w-2xl text-center sm:mb-5">
                    <span
                        class="block mb-1 text-xs font-bold tracking-wide uppercase text-camp-sand-dark">{{ __('web.bootcamp.audience.eyebrow') }}</span>
                    <h2 class="mb-1 text-2xl font-black sm:text-4xl">{{ __('web.bootcamp.audience.title') }}</h2>
                    <p class="text-xs text-camp-muted sm:text-base">{{ __('web.bootcamp.audience.subtitle') }}</p>
                </div>

                @if ($content['audience'] !== [])
                    <div class="grid grid-cols-2 gap-3 mb-3 sm:mb-5 sm:gap-4 lg:grid-cols-4">
                        @foreach ($content['audience'] as $persona)
                            <div class="p-3 border rounded-3xl border-camp-border bg-camp-cream/60 sm:p-4">
                                @if ($persona['icon'])
                                    <div
                                        class="flex items-center justify-center mb-2 text-sm font-bold h-9 w-9 rounded-xl bg-camp-dark text-camp-sand sm:h-10 sm:w-10 sm:text-base">
                                        {{ $persona['icon'] }}</div>
                                @endif
                                <h3 class="mb-1 text-sm font-bold sm:mb-2 sm:text-lg">{{ $persona['title'] }}</h3>
                                @if ($persona['description'])
                                    <p class="text-[11px] leading-relaxed text-camp-muted sm:text-xs">
                                        {{ $persona['description'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($content['not_for'])
                    <div
                        class="max-w-2xl p-3 mx-auto text-center border rounded-2xl border-amber-200 bg-amber-50 sm:p-5">
                        <span
                            class="block mb-1 text-xs font-bold font-camp-heading text-amber-900 sm:text-sm">{{ __('web.bootcamp.audience.not_for') }}</span>
                        <p class="text-[11px] leading-relaxed text-amber-800 sm:text-xs">{{ $content['not_for'] }}
                        </p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ==================== SCHEDULE ==================== --}}
    @if ($bootcamp->scheduleWeeks !== [])
        <section id="schedule" class="py-4 border-t border-camp-border bg-camp-cream sm:py-8">
            <div class="px-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-col justify-between gap-2 mb-3 sm:mb-5 sm:gap-4 md:flex-row md:items-end">
                    <div class="max-w-2xl text-start">
                        <span
                            class="block mb-1 text-xs font-bold tracking-wide uppercase text-camp-sand-dark">{{ __('web.bootcamp.schedule.eyebrow') }}</span>
                        <h2 class="mb-1 text-2xl font-black sm:text-4xl">{{ __('web.bootcamp.schedule.title') }}</h2>
                        @if ($bootcamp->scheduleSummary)
                            <p class="text-xs text-camp-muted sm:text-base">{{ $bootcamp->scheduleSummary }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold sm:gap-3">
                        @if ($stats['weeks'])
                            <span
                                class="rounded-xl border border-camp-border bg-white px-3 py-1.5">{{ trans_choice('web.bootcamp.schedule.weeks', $stats['weeks'], ['count' => $stats['weeks']]) }}</span>
                        @endif
                        <span
                            class="rounded-xl border border-camp-border bg-white px-3 py-1.5">{{ trans_choice('web.bootcamp.schedule.sessions', $stats['sessions'], ['count' => $stats['sessions']]) }}</span>
                        @if ($stats['hours'])
                            <span
                                class="rounded-xl bg-camp-dark px-3 py-1.5 text-camp-sand">{{ trans_choice('web.bootcamp.schedule.hours', $stats['hours'], ['count' => $stats['hours']]) }}</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-2 sm:gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($bootcamp->scheduleWeeks as $week)
                        @php $isLastWeek = $loop->last && $loop->count > 1; @endphp
                        <div wire:key="camp-schedule-{{ $week['week'] }}"
                            class="space-y-2.5 rounded-3xl border p-3 sm:p-4 {{ $isLastWeek ? 'border-camp-darker bg-camp-dark text-white' : 'border-camp-border bg-white' }}">
                            <div
                                class="flex items-center justify-between border-b pb-3 {{ $isLastWeek ? 'border-white/10' : 'border-camp-border' }}">
                                <h3 class="text-sm font-bold sm:text-base {{ $isLastWeek ? 'text-camp-sand' : '' }}">
                                    {{ $week['label'] }}</h3>
                                <span
                                    class="rounded px-2 py-0.5 text-[11px] font-semibold {{ $isLastWeek ? 'bg-white/10 text-white/80' : 'bg-camp-cream text-camp-muted' }}">{{ trans_choice('web.bootcamp.schedule.sessions', count($week['sessions']), ['count' => count($week['sessions'])]) }}</span>
                            </div>
                            @foreach ($week['sessions'] as $session)
                                <div
                                    class="rounded-2xl border p-3 {{ $isLastWeek ? 'border-white/10 bg-white/5' : 'border-camp-border/60 bg-camp-cream/60' }}">
                                    <div class="flex items-center justify-between gap-2 text-xs">
                                        <span class="font-bold">{{ $session['day'] }} <span
                                                class="font-normal opacity-60">{{ $session['date'] }}</span></span>
                                        <span
                                            class="text-[11px] {{ $isLastWeek ? 'text-white/60' : 'text-camp-muted' }}">{{ collect([$session['module'], $session['time']])->filter()->implode(' · ') }}</span>
                                    </div>
                                    @if ($session['title'])
                                        <p
                                            class="mt-0.5 text-xs font-medium {{ $isLastWeek ? 'text-white/90' : 'text-camp-charcoal' }}">
                                            {{ $session['title'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== PRICING ==================== --}}
    <section id="pricing" class="relative py-5 overflow-hidden bg-white border-t border-camp-border sm:py-10">
        <div
            class="pointer-events-none absolute left-1/2 top-1/2 h-96 w-96 -translate-x-1/2 -translate-y-1/2 rounded-full bg-camp-sand/15 blur-3xl sm:h-[600px] sm:w-[600px]">
        </div>

        <div class="relative px-3 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="mx-auto mb-2.5 max-w-3xl text-center sm:mb-5">
                <span
                    class="block mb-1 text-xs font-bold tracking-wide uppercase text-camp-sand-dark">{{ __('web.bootcamp.pricing.eyebrow') }}</span>
                <h2 class="mb-1 text-2xl font-black sm:text-5xl">{{ __('web.bootcamp.pricing.title') }}</h2>
                @if ($cohort && $cohort['start_at'])
                    <p class="text-xs text-camp-muted sm:text-lg">
                        {{ __('web.bootcamp.pricing.starts_at', ['date' => $cohort['start_at']]) }}</p>
                @endif
            </div>

            <div
                class="relative max-w-2xl p-3 mx-auto text-white border shadow-2xl rounded-3xl border-camp-darker bg-camp-dark sm:p-7">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-white/10 sm:pb-6">
                    @if ($hasDiscount)
                        <div
                            class="inline-flex items-center gap-1.5 rounded-full bg-camp-sand px-3 py-1 text-xs font-bold text-camp-dark">
                            <span>{{ $content['offer_label'] ?? __('web.bootcamp.pricing.offer') }}</span>
                        </div>
                    @else
                        <span class="text-sm font-semibold text-white/80">{{ $course->title }}</span>
                    @endif
                    <div class="text-end">
                        @if ($hasDiscount)
                            <span
                                class="block text-xs line-through text-white/50">{{ number_format((float) $course->price) }}
                                {{ $currencyLabel }}</span>
                        @endif
                        @if ($priceText !== null)
                            <div class="flex items-baseline gap-1">
                                <span
                                    class="text-3xl font-black font-camp-heading text-camp-sand sm:text-4xl">{{ $priceText }}</span>
                                @unless ($course->isFree)
                                    <span class="text-xs text-white/70">{{ $currencyLabel }}</span>
                                @endunless
                            </div>
                        @else
                            <span class="text-sm text-white/70">{{ __('web.courses.price_tbd') }}</span>
                        @endif
                    </div>
                </div>

                @if ($canBook && $cohort['deadline_at'])
                    <div wire:ignore class="py-3 border-b border-white/10 sm:py-6" x-data="{
                        end: new Date(@js($cohort['deadline_at'])).getTime(),
                        parts: { days: '00', hours: '00', minutes: '00', seconds: '00' },
                        tick() {
                            const left = Math.max(0, this.end - Date.now());
                            const pad = (value) => String(value).padStart(2, '0');
                            this.parts = {
                                days: pad(Math.floor(left / 86400000)),
                                hours: pad(Math.floor(left / 3600000) % 24),
                                minutes: pad(Math.floor(left / 60000) % 60),
                                seconds: pad(Math.floor(left / 1000) % 60),
                            };
                        },
                    }"
                        x-init="tick();
                        setInterval(() => tick(), 1000)">
                        <span
                            class="block mb-2 text-xs text-center text-white/60">{{ __('web.bootcamp.pricing.closes_in') }}</span>
                        <div class="grid max-w-md grid-cols-4 gap-2 mx-auto text-center sm:gap-4">
                            @foreach (['days', 'hours', 'minutes', 'seconds'] as $unit)
                                <div class="p-2 border rounded-2xl border-white/10 bg-white/5 sm:p-3">
                                    <span
                                        class="block font-camp-heading text-lg font-bold sm:text-2xl {{ $unit === 'seconds' ? 'text-camp-green' : 'text-camp-sand' }}"
                                        x-text="parts.{{ $unit }}">00</span>
                                    <span
                                        class="text-[10px] text-white/60">{{ __('web.bootcamp.pricing.' . $unit) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($cohort && $cohort['seats_percent'] !== null)
                    <div class="py-3 space-y-2 border-b border-white/10 sm:py-6">
                        <div class="flex items-center justify-between text-xs">
                            <span
                                class="font-medium text-white/80">{{ __('web.bootcamp.pricing.seats_booked') }}</span>
                            <span
                                class="font-bold text-camp-sand">{{ __('web.bootcamp.pricing.seats_percent', ['percent' => $cohort['seats_percent']]) }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-white/10 sm:h-2.5">
                            <div class="h-full rounded-full bg-gradient-to-l from-camp-sand to-camp-green"
                                style="width: {{ $cohort['seats_percent'] }}%;"></div>
                        </div>
                    </div>
                @endif

                @if ($content['included_features'] !== [])
                    <div class="py-3 space-y-2 text-xs text-white/90 sm:py-6 sm:text-sm">
                        @foreach ($content['included_features'] as $feature)
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="flex items-center justify-center w-4 h-4 text-xs font-bold rounded-full shrink-0 bg-camp-green/20 text-camp-green sm:h-5 sm:w-5">✓</span>
                                <span>{{ $feature }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-center gap-2 pt-2 pb-3 text-xs text-white/70">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1">🔒
                        <span>{{ __('web.bootcamp.pricing.secure_payment') }}</span></span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1">🎓
                        <span>{{ __('web.bootcamp.pricing.certificate') }}</span></span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/5 px-3 py-1">💬
                        <span>{{ __('web.bootcamp.pricing.support') }}</span></span>
                </div>

                <div class="space-y-2.5">
                    @if ($canBook)
                        <a href="#book"
                            class="flex w-full items-center justify-center gap-2 rounded-xl bg-camp-sand py-3.5 text-sm font-bold text-camp-dark shadow-xl transition-all hover:bg-white sm:py-4 sm:text-base">
                            <span>{{ __('web.bootcamp.book_seat_now') }}</span>
                            <svg class="h-4 w-4 {{ $arrowClass }}" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    @else
                        <button type="button" disabled
                            class="w-full cursor-not-allowed rounded-xl bg-white/10 py-3.5 text-sm font-bold text-white/60 sm:py-4 sm:text-base">
                            {{ __('web.bootcamp.registration_closed') }}
                        </button>
                    @endif
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                            class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/5 py-3 text-xs font-semibold text-white transition-all hover:bg-white/10 sm:py-3.5 sm:text-sm">
                            <svg class="w-4 h-4 text-camp-sand" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <span>{{ __('web.bootcamp.ask_advisor') }}</span>
                        </a>
                    @endif
                </div>

                <p class="pt-3 text-center text-[11px] text-white/50">{{ __('web.bootcamp.pricing.after_payment') }}
                </p>
            </div>
        </div>
    </section>

    {{-- ==================== FAQ ==================== --}}
    @if ($content['faqs'] !== [])
        <section id="faq" class="py-4 border-t border-camp-border bg-camp-cream sm:py-8"
            x-data="{ open: 0 }">
            <div class="max-w-4xl px-3 mx-auto sm:px-6 lg:px-8">
                <div class="mb-2.5 text-center sm:mb-5">
                    <span
                        class="block mb-1 text-xs font-bold tracking-wide uppercase text-camp-sand-dark">{{ __('web.bootcamp.faq.eyebrow') }}</span>
                    <h2 class="mb-1 text-2xl font-black sm:text-4xl">{{ __('web.bootcamp.faq.title') }}</h2>
                    <p class="text-xs text-camp-muted sm:text-base">{{ __('web.bootcamp.faq.subtitle') }}</p>
                </div>

                <div class="space-y-2 sm:space-y-2">
                    @foreach ($content['faqs'] as $faq)
                        <div class="overflow-hidden bg-white border rounded-2xl border-camp-border">
                            <button type="button"
                                x-on:click="open = open === {{ $loop->index }} ? null : {{ $loop->index }}"
                                x-bind:aria-expanded="open === {{ $loop->index }}"
                                class="flex items-center justify-between w-full gap-3 p-3 text-sm font-bold text-start font-camp-heading sm:p-5 sm:text-base">
                                <span>{{ $faq['question'] }}</span>
                                <span
                                    class="flex items-center justify-center w-5 h-5 text-xs transition-transform rounded-full shrink-0 bg-camp-cream text-camp-muted"
                                    x-bind:class="open === {{ $loop->index }} ? 'rotate-180' : ''">▼</span>
                            </button>
                            <div x-show="open === {{ $loop->index }}" x-collapse
                                @if (!$loop->first) x-cloak @endif
                                class="px-3 pb-3 text-xs leading-relaxed text-camp-muted sm:px-5 sm:pb-5 sm:text-sm">
                                {{ $faq['answer'] }}
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-2.5 flex flex-wrap items-center justify-center gap-3 text-center sm:mt-5">
                    @if ($whatsappUrl)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                            class="rounded-xl border border-camp-border bg-white px-5 py-2.5 text-xs font-semibold transition-all hover:bg-camp-sand-light sm:text-sm">{{ __('web.bootcamp.faq.ask_us') }}</a>
                    @endif
                    <a href="#book"
                        class="rounded-xl bg-camp-dark px-5 py-2.5 text-xs font-semibold text-camp-sand transition-all hover:bg-camp-darker sm:text-sm">{{ __('web.bootcamp.book_seat') }}</a>
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== FINAL CTA ==================== --}}
    <section class="relative py-5 overflow-hidden text-white bg-camp-dark sm:py-8">
        <div class="relative px-3 mx-auto text-center max-w-7xl sm:px-6 lg:px-8">
            @if ($content['pillars'] !== [])
                <div
                    class="mb-3 inline-flex flex-wrap items-center justify-center gap-1.5 rounded-full bg-white/10 px-3.5 py-1 text-[11px] font-semibold text-camp-sand sm:gap-2 sm:text-xs">
                    @foreach ($content['pillars'] as $pillar)
                        <span class="{{ $loop->last ? 'font-bold text-white' : '' }}">{{ $pillar['title'] }}</span>
                        @unless ($loop->last)
                            <span>{{ $isRtl ? '←' : '→' }}</span>
                        @endunless
                    @endforeach
                </div>
            @endif

            <h2 class="max-w-3xl mx-auto mb-2 text-2xl font-black leading-tight sm:mb-3 sm:text-5xl">
                {{ $content['final_cta_title'] ?? $course->title }}</h2>

            @if ($content['final_cta_text'])
                <p class="max-w-2xl mx-auto mb-3 text-xs leading-relaxed text-white/80 sm:mb-5 sm:text-lg">
                    {{ $content['final_cta_text'] }}</p>
            @endif

            <div class="flex flex-col items-center justify-center max-w-md gap-3 mx-auto sm:flex-row sm:gap-4">
                <a href="#book"
                    class="w-full rounded-xl bg-camp-sand px-8 py-3.5 text-sm font-bold text-camp-dark shadow-2xl transition-all hover:bg-white sm:w-auto sm:py-4 sm:text-base">{{ __('web.bootcamp.book_seat_now') }}</a>
                @if ($bootcamp->curriculumWeeks !== [])
                    <a href="#curriculum"
                        class="w-full rounded-xl border border-white/10 bg-white/10 px-7 py-3.5 text-sm font-semibold text-white transition-all hover:bg-white/20 sm:w-auto sm:py-4 sm:text-base">{{ __('web.bootcamp.review_curriculum') }}</a>
                @endif
            </div>
        </div>
    </section>

    {{-- ==================== RELATED COURSES (same as course-show) ==================== --}}
    @if ($relatedCourses !== [])
        <section class="py-4 border-t camp-site-font border-camp-border bg-camp-cream sm:py-8">
            <div class="flex flex-col items-center gap-3 px-3 sm:gap-4 sm:px-6 lg:px-[68px]">
                <div class="flex flex-col w-full gap-3 sm:gap-4">
                    <div class="flex items-center justify-center">
                        <p class="text-[32px] font-bold text-[#322f37]">{{ __('web.course.interested_title') }}</p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($relatedCourses as $related)
                            <x-course-card :card="$related" />
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== MOBILE STICKY BOOKING BAR ==================== --}}
    {{-- Hidden while the hero (booking card) or pricing section is on screen --}}
    <div x-data="{ inView: { about: true }, get show() { return !Object.values(this.inView).some(Boolean) } }" x-init="const io = new IntersectionObserver((entries) => entries.forEach((e) => inView[e.target.id] = e.isIntersecting), { threshold: 0.15 });
    ['about', 'pricing'].forEach((id) => document.getElementById(id) && io.observe(document.getElementById(id)))" x-show="show" x-cloak
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full opacity-0"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-y-full opacity-0"
        class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-between gap-3 border-t border-white/10 bg-camp-dark/95 px-4 py-3 shadow-[0_-8px_25px_rgba(0,0,0,0.3)] backdrop-blur-md lg:hidden">
        <div>
            @if ($priceText !== null)
                <div class="flex items-baseline gap-1 text-white">
                    <span class="text-lg font-black font-camp-heading text-camp-sand">{{ $priceText }}</span>
                    @unless ($course->isFree)
                        <span class="text-[10px] text-white/70">{{ $currencyLabel }}</span>
                    @endunless
                    @if ($hasDiscount)
                        <span
                            class="ms-1 text-[10px] text-white/40 line-through">{{ number_format((float) $course->price) }}</span>
                    @endif
                </div>
            @endif
            @if ($cohort && $cohort['seats_percent'] !== null)
                <div class="text-[10px] font-medium text-camp-green">
                    {{ __('web.bootcamp.pricing.seats_percent', ['percent' => $cohort['seats_percent']]) }}</div>
            @endif
        </div>
        <a href="#book"
            class="inline-flex items-center gap-1.5 rounded-xl bg-camp-sand px-5 py-2.5 font-camp-heading text-xs font-bold text-camp-dark shadow-md transition-all active:scale-95">
            <span>{{ __('web.bootcamp.book_seat') }}</span>
            <svg class="h-3.5 w-3.5 {{ $arrowClass }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
            </svg>
        </a>
    </div>
</div>
