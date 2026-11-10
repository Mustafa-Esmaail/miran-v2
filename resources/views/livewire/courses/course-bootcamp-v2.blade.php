{{-- Livewire needs ONE root element --}}
{{-- Bootcamp design 2 — converted from miran-ai-bootcamp-v2.html --}}
<div
    class="camp-page camp2-page overflow-x-hidden bg-camp2-page pb-20 font-camp2-body text-[17px] leading-[1.6] text-camp2-slate sm:leading-[1.7] md:pb-0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;500;600;700;800&family=IBM+Plex+Sans+Arabic:wght@400;600;700&display=swap"
        rel="stylesheet">

    @php
        $content = $bootcamp->content;
        $stats = $bootcamp->stats;
        $modules = collect($bootcamp->curriculumWeeks)->flatMap(fn(array $week): array => $week['modules'])->values();
        $methodParagraphs = collect(preg_split('/\R+/u', (string) $content['method_note']))
            ->map(fn(string $paragraph): string => trim($paragraph))
            ->filter()
            ->values();
        $sessionHours =
            $stats['hours'] && $stats['sessions'] ? (int) round($stats['hours'] / $stats['sessions']) : null;
        $ctaClass =
            'inline-flex min-h-12 items-center justify-center rounded-full bg-camp2-brown px-7 font-camp-heading text-lg font-bold text-white no-underline transition-colors hover:bg-camp2-brown-dark hover:text-white focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-camp2-brown-soft motion-reduce:transition-none';
        $sectionImage = fn(string $name): string => asset('img/bootcamp-v2/' . $name);

        // Pillar icons, reused in order (layers, image, target, cycle).
        $pillarIcons = [
            '<path d="M12 3l9 5-9 5-9-5 9-5z"/><path d="M3 13l9 5 9-5"/>',
            '<path d="M4 5h16v14H4z"/><circle cx="9" cy="10" r="1.5"/><path d="M4 16l4-4 4 4 3-3 5 5"/>',
            '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3.5"/>',
            '<path d="M20 11a8 8 0 0 0-14.5-4.5L4 8"/><path d="M4 4v4h4"/><path d="M4 13a8 8 0 0 0 14.5 4.5L20 16"/><path d="M20 20v-4h-4"/>',
        ];

        // Method wheel: steps sit on a circle (r = 36 in a 100×100 box), read in the page direction.
        $wheelSteps = array_values($content['method_steps']);
        $wheelCount = count($wheelSteps);
        $wheelSign = $isRtl ? -1 : 1;
        $wheelNodes = [];
        $wheelArrows = [];
        foreach ($wheelSteps as $index => $step) {
            $angle = (2 * M_PI * $index) / max(1, $wheelCount);
            $wheelNodes[] = [
                'title' => $step['title'] ?? $step['description'],
                'x' => round(50 + $wheelSign * 36 * sin($angle), 2),
                'y' => round(50 - 36 * cos($angle), 2),
            ];

            if ($wheelCount > 1) {
                $middle = (2 * M_PI * ($index + 0.5)) / $wheelCount;
                $wheelArrows[] = [
                    'x' => round(50 + $wheelSign * 36 * sin($middle), 2),
                    'y' => round(50 - 36 * cos($middle), 2),
                    'rotate' => round(rad2deg(atan2(sin($middle), $wheelSign * cos($middle))), 1),
                ];
            }
        }
        $wheelSecondsPerStep = 2;
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

            <div class="grid items-start w-full gap-4 pb-4 sm:gap-8 sm:pb-10 lg:grid-cols-12 lg:pb-12">
                <div class="relative w-full h-full overflow-hidden rounded-3xl bg-black/20 lg:col-span-8 ">
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
                        class="flex flex-col gap-3 rounded-3xl border border-[#ced0d3] bg-white p-4 sm:gap-6 sm:p-8 lg:p-10">
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
                                        class="col-span-1 w-full rounded-3xl border border-[#ced0d3] px-4 py-2.5 text-base sm:px-6 sm:py-3"
                                        placeholder="{{ __('web.course.quantity') }}">
                                    <select wire:model="courseGroupId"
                                        class="col-span-2 w-full rounded-3xl border border-[#ced0d3] px-4 py-2.5 text-base sm:px-6 sm:py-3">
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
                                        class="w-full rounded-3xl bg-[#99745c] px-4 py-2.5 text-lg sm:px-6 sm:py-3 font-semibold text-white transition hover:bg-[#86634e] hover:shadow-[0_12px_30px_rgba(153,116,92,0.22)]"
                                        wire:loading.attr="disabled">
                                        {{ __('web.course.buy_now') }}
                                    </button>
                                    <button type="submit"
                                        class="w-full rounded-3xl border border-[#99745c] px-4 py-2.5 text-lg sm:px-6 sm:py-3 font-semibold text-[#99745c] transition hover:bg-[#99745c] hover:text-white hover:shadow-[0_12px_30px_rgba(153,116,92,0.18)]"
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

    {{-- ==================== PILLARS + OUTCOMES VISUAL ==================== --}}
    @php
        $outcomeCards = [
            'inset-x-[18%] top-[14.5%]',
            'start-0 top-[34%] w-[68%]',
            'end-0 top-[52.5%] w-[68%]',
            'inset-x-[18%] top-[77%]',
        ];
        $outcomeIcons = [
            ['module-3.webp', '-start-[2%] -top-[9%] w-[24%] sm:-start-[5%] sm:w-[28%]'],
            ['module-4.webp', '-end-[2%] top-[25%] w-[26%] sm:-end-[5%] sm:w-[30%]'],
            ['module-5.webp', '-start-[2%] top-[47%] w-[26%] sm:-start-[5%] sm:w-[30%]'],
            ['module-7.webp', '-bottom-[7%] -end-[2%] w-[25%] sm:-end-[5%] sm:w-[29%]'],
        ];
    @endphp
    <section class="bg-white border-y border-camp2-line">
        <div
            class="mx-auto grid max-w-[1120px] items-center gap-8 px-3 py-[clamp(20px,3vw,40px)] sm:px-6 lg:grid-cols-12 lg:gap-10">
            <div class="mx-auto w-full max-w-[340px] {{ $content['pillars'] !== [] ? 'lg:col-span-5' : 'lg:col-span-12' }}"
                aria-label="{{ __('web.bootcamp.v2.outcomes.title') }}">
                <div class="relative aspect-[1/1.16] min-h-[400px] w-full">
                    <div
                        class="absolute inset-x-[12%] top-0 h-[30%] rounded-t-[28px] border-8 border-b-0 border-camp2-slate">
                    </div>
                    <div
                        class="absolute inset-x-[12%] bottom-0 top-[72%] rounded-b-[28px] border-8 border-t-0 border-camp2-slate">
                    </div>
                    <div class="absolute inset-x-[12%] top-[6.5%] text-center text-sm font-bold text-camp2-brown">
                        {{ __('web.bootcamp.v2.outcomes.title') }}</div>

                    @foreach (__('web.bootcamp.v2.outcomes.items') as $outcome)
                        <div
                            class="absolute {{ $outcomeCards[$loop->index] ?? '' }} flex flex-col rounded-[14px] border border-camp2-line bg-white px-4 py-2.5 shadow-[0_8px_20px_rgba(43,57,57,0.08)]">
                            <span
                                class="text-[15px] font-bold leading-[1.45] text-camp2-ink">{{ $outcome['title'] }}</span>
                            <span
                                class="text-[13px] leading-[1.45] text-camp2-muted">{{ $outcome['subtitle'] }}</span>
                        </div>
                    @endforeach

                    @foreach ($outcomeIcons as [$icon, $position])
                        <img src="{{ $sectionImage($icon) }}" alt="" aria-hidden="true" loading="lazy"
                            decoding="async" class="pointer-events-none absolute h-auto {{ $position }}">
                    @endforeach
                </div>
            </div>

            @if ($content['pillars'] !== [])
                <div class="flex flex-col gap-2 sm:gap-3 lg:col-span-7">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-1.5 text-[15px] font-semibold text-camp2-brown">
                            <span aria-hidden="true"
                                class="size-[9px] flex-none rotate-45 rounded-[2px] bg-camp2-brown-soft"></span>
                            {{ __('web.bootcamp.v2.pillars.eyebrow') }}
                        </div>
                        <h2 class="text-balance text-[clamp(24px,3.4vw,38px)] font-bold leading-[1.45] text-camp2-ink">
                            {{ __('web.bootcamp.v2.pillars.title') }}</h2>
                    </div>

                    <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,230px),1fr))] gap-x-3 gap-y-2.5">
                        @foreach ($content['pillars'] as $pillar)
                            <div class="flex items-start gap-3">
                                <span
                                    class="inline-flex size-11 flex-none items-center justify-center rounded-[14px] bg-camp2-sand-light text-camp2-brown">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">{!! $pillarIcons[$loop->index % count($pillarIcons)] !!}</svg>
                                </span>
                                <div class="min-w-0">
                                    <h3 class="text-[19px] font-bold leading-normal text-camp2-ink">
                                        {{ $pillar['title'] }}</h3>
                                    @if ($pillar['description'])
                                        <p class="text-base leading-[1.7] text-camp2-muted">
                                            {{ $pillar['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- ==================== METHOD ==================== --}}
    @if ($wheelNodes !== [] || $methodParagraphs->isNotEmpty())
        <section id="method" class="bg-camp2-slate text-camp2-page">
            <div
                class="mx-auto flex max-w-[1120px] flex-wrap items-center justify-center gap-x-6 gap-y-2 px-3 py-[clamp(12px,2.5vw,32px)] sm:gap-y-3 sm:px-6">
                <div class="flex min-w-0 flex-[1_1_300px] flex-col gap-1.5">
                    <div class="flex items-center gap-1.5 text-[15px] font-semibold text-camp2-sand">
                        <span aria-hidden="true"
                            class="size-[9px] flex-none rotate-45 rounded-[2px] bg-camp2-sand"></span>
                        {{ __('web.bootcamp.v2.method.eyebrow') }}
                    </div>
                    <h2 class="text-balance text-[clamp(24px,3.6vw,42px)] font-bold leading-[1.45] text-white">
                        {{ __('web.bootcamp.method.title') }}</h2>
                    @foreach ($methodParagraphs as $paragraph)
                        <p class="leading-[1.85] text-camp2-mist">{{ $paragraph }}</p>
                    @endforeach
                </div>

                @if ($wheelNodes !== [])
                    <div class="w-full min-w-0 flex-[0_1_400px] pb-3 sm:pb-6">
                        <div class="relative w-full aspect-square"
                            style="--camp2-wheel-duration: {{ $wheelCount * $wheelSecondsPerStep }}s">
                            <svg viewBox="0 0 100 100" aria-hidden="true" class="absolute inset-0 size-full">
                                <circle cx="50" cy="50" r="36" fill="none" stroke="#7D8E8E"
                                    stroke-width="0.6" stroke-dasharray="1.6 2.2" />
                                <g fill="none" stroke="#D9B79F" stroke-width="1.1" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    @foreach ($wheelArrows as $arrow)
                                        <path d="M-1.8 -2.3L1.8 0L-1.8 2.3"
                                            transform="translate({{ $arrow['x'] }} {{ $arrow['y'] }}) rotate({{ $arrow['rotate'] }})" />
                                    @endforeach
                                </g>
                                <g class="camp2-wheel-spin">
                                    <circle cx="50" cy="14" r="1.8" fill="#D9B79F" />
                                </g>
                            </svg>

                            <div
                                class="absolute left-1/2 top-1/2 flex w-[84px] -translate-x-1/2 -translate-y-1/2 flex-col items-center gap-1 text-center text-[13px] font-semibold leading-normal text-camp2-sand">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">{!! $pillarIcons[3] !!}</svg>
                                <span>{{ __('web.bootcamp.v2.method.loop') }}</span>
                            </div>

                            <ol class="absolute inset-0 p-0 m-0 list-none">
                                @foreach ($wheelNodes as $node)
                                    <li class="absolute flex w-24 -translate-x-1/2 -translate-y-[18px] flex-col items-center gap-1.5 text-center"
                                        style="top: {{ $node['y'] }}%; left: {{ $node['x'] }}%">
                                        <span
                                            class="camp2-wheel-node inline-flex size-9 flex-none items-center justify-center rounded-full border-[1.5px] border-camp2-sand bg-camp2-ink font-camp-heading text-[15px] font-bold text-camp2-sand"
                                            style="animation-delay: {{ $loop->index * $wheelSecondsPerStep }}s">{{ $loop->iteration }}</span>
                                        <span
                                            class="rounded-md bg-camp2-slate px-[5px] py-px text-[13.5px] font-semibold leading-[1.45] text-white">{{ $node['title'] }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- ==================== CURRICULUM ==================== --}}
    @if ($modules->isNotEmpty())
        <section id="curriculum"
            class="mx-auto flex max-w-[1120px] flex-col gap-2 px-3 py-[clamp(14px,3vw,36px)] sm:gap-3 sm:px-6">
            <div class="flex flex-col gap-1.5">
                <div class="flex items-center gap-1.5 text-[15px] font-semibold text-camp2-brown">
                    <span aria-hidden="true"
                        class="size-[9px] flex-none rotate-45 rounded-[2px] bg-camp2-brown-soft"></span>
                    {{ __('web.bootcamp.v2.curriculum.eyebrow') }}
                </div>
                <h2 class="text-balance text-[clamp(26px,3.6vw,42px)] font-bold leading-[1.4] text-camp2-ink">
                    {{ __('web.bootcamp.v2.curriculum.title') }}</h2>
            </div>

            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,420px),1fr))] items-start gap-2 sm:gap-2.5">
                @foreach ($modules as $module)
                    @php
                        $isCapstone = (bool) $module['is_capstone'];
                        $moduleImage = $isCapstone
                            ? 'module-capstone.webp'
                            : 'module-' . (($loop->index % 7) + 1) . '.webp';
                    @endphp
                    <article
                        class="flex flex-col gap-2 rounded-[20px] border p-[clamp(8px,1.5vw,16px)] {{ $isCapstone ? 'border-camp2-slate bg-camp2-slate text-camp2-page' : 'border-camp2-line bg-white' }}">
                        <div class="flex items-center gap-3">
                            <img src="{{ $sectionImage($moduleImage) }}" alt="" loading="lazy"
                                decoding="async"
                                class="-my-1.5 -ms-1 size-14 flex-none sm:-my-2.5 sm:-ms-1.5 sm:size-20 object-contain">
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="text-[13px] font-semibold leading-normal {{ $isCapstone ? 'text-camp2-sand' : 'text-camp2-brown' }}">{{ __('web.bootcamp.module_number', ['number' => (int) $module['number']]) }}</span>
                                <h3
                                    class="text-[clamp(19px,2vw,22px)] font-bold leading-normal {{ $isCapstone ? 'text-white' : 'text-camp2-ink' }}">
                                    {{ $module['title'] }}</h3>
                            </div>
                        </div>

                        @if ($module['description'])
                            <div class="leading-[1.85]">{!! $module['description'] !!}</div>
                        @endif

                        @if ($module['outcome'])
                            <div
                                class="flex flex-col gap-0.5 rounded-[14px] px-3 py-1.5 sm:px-4 sm:py-2 {{ $isCapstone ? 'bg-camp2-ink' : 'bg-camp2-sand-light' }}">
                                <span
                                    class="text-sm font-bold {{ $isCapstone ? 'text-camp2-sand' : 'text-camp2-brown-dark' }}">{{ rtrim(__('web.bootcamp.curriculum.outcome'), ':') }}</span>
                                <span
                                    class="font-semibold {{ $isCapstone ? 'text-white' : 'text-camp2-ink' }}">{{ $module['outcome'] }}</span>
                            </div>
                        @endif

                        @if ($module['bullets'] !== [])
                            <details class="group">
                                <summary
                                    class="inline-flex min-h-11 cursor-pointer list-none items-center gap-1 rounded-lg text-base font-bold focus-visible:outline-3 focus-visible:outline-offset-3 focus-visible:outline-camp2-brown-soft [&::-webkit-details-marker]:hidden {{ $isCapstone ? 'text-camp2-sand' : 'text-camp2-brown' }}">
                                    {{ __('web.bootcamp.v2.curriculum.practice') }}
                                    <svg class="size-[18px] transition-transform group-open:rotate-180 motion-reduce:transition-none"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M6 9l6 6 6-6" />
                                    </svg>
                                </summary>
                                <ol class="mt-1.5 flex list-none flex-col p-0">
                                    @foreach ($module['bullets'] as $bullet)
                                        <li
                                            class="flex gap-2 border-t py-1.5 sm:gap-3 sm:py-2 {{ $isCapstone ? 'border-white/10' : 'border-[#E6E9E9]' }}">
                                            <span
                                                class="inline-flex size-7 flex-none items-center justify-center rounded-full font-camp-heading text-sm font-bold {{ $isCapstone ? 'bg-white/10' : 'bg-camp2-chip' }}">{{ $loop->iteration }}</span>
                                            <span>{{ $bullet }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            </details>
                        @endif
                    </article>
                @endforeach
            </div>

            <div class="flex justify-center pt-1">
                <a href="#book" class="{{ $ctaClass }}">{{ __('web.bootcamp.book_seat') }}</a>
            </div>
        </section>
    @endif

    {{-- ==================== AUDIENCE ==================== --}}
    @if ($content['audience'] !== [] || $content['not_for'])
        <section class="bg-white border-y border-camp2-line">
            <div class="mx-auto flex max-w-[1120px] flex-col gap-2 px-3 py-[clamp(14px,3vw,36px)] sm:gap-3 sm:px-6">
                <div class="flex items-center gap-1.5">
                    <img src="{{ $sectionImage('audience.webp') }}" alt="" loading="lazy" decoding="async"
                        class="-my-2 -ms-1 size-14 flex-none sm:-my-3.5 sm:-ms-1.5 sm:size-20 object-contain">
                    <h2
                        class="min-w-0 text-balance text-[clamp(26px,3.6vw,42px)] font-bold leading-[1.4] text-camp2-ink">
                        {{ __('web.bootcamp.audience.title') }}</h2>
                </div>

                <div class="flex flex-wrap items-stretch gap-2 sm:gap-3">
                    @if ($content['audience'] !== [])
                        <div
                            class="flex min-w-0 flex-[999_1_460px] flex-col gap-1.5 rounded-3xl sm:gap-2.5 bg-camp2-page p-[clamp(10px,2vw,20px)]">
                            <div class="flex items-center gap-1">
                                <img src="{{ $sectionImage('audience-fits.webp') }}" alt="" loading="lazy"
                                    decoding="async"
                                    class="-my-1.5 -ms-1 size-11 flex-none sm:-my-2.5 sm:size-[58px] object-contain">
                                <h3 class="min-w-0 text-[22px] font-bold text-camp2-ink">
                                    {{ __('web.bootcamp.v2.audience.fits') }}</h3>
                            </div>
                            <ul class="flex flex-col gap-2 p-0 m-0 list-none">
                                @foreach ($content['audience'] as $item)
                                    <li class="flex items-start gap-3">
                                        <svg class="mt-[3px] size-6 flex-none" viewBox="0 0 24 24" fill="none"
                                            stroke="#86624B" stroke-width="2.2" stroke-linecap="round"
                                            stroke-linejoin="round" aria-hidden="true">
                                            <path d="M5 12.5l4.5 4.5L19 7.5" />
                                        </svg>
                                        <span>
                                            @if ($item['title'])
                                                <span class="font-semibold text-camp2-ink">{{ $item['title'] }}</span>
                                            @endif
                                            {{ $item['description'] }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($content['not_for'])
                        <div
                            class="flex min-w-0 flex-[1_1_300px] flex-col gap-1.5 rounded-3xl sm:gap-2.5 border border-camp2-line-strong p-[clamp(10px,2vw,20px)]">
                            <div class="flex items-center gap-1">
                                <img src="{{ $sectionImage('audience-not-fit.webp') }}" alt=""
                                    loading="lazy" decoding="async"
                                    class="-my-1.5 -ms-1 size-11 flex-none sm:-my-2.5 sm:size-[58px] object-contain">
                                <h3 class="min-w-0 text-[22px] font-bold text-camp2-ink">
                                    {{ __('web.bootcamp.v2.audience.not_fit') }}</h3>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="mt-[3px] size-6 flex-none" viewBox="0 0 24 24" fill="none"
                                    stroke="#4A5959" stroke-width="2.2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <path d="M6 6l12 12M18 6L6 18" />
                                </svg>
                                <span class="text-camp2-muted">{{ $content['not_for'] }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== FAQ ==================== --}}
    @if ($content['faqs'] !== [])
        <section id="faq"
            class="mx-auto flex max-w-[1120px] flex-col gap-2 px-3 py-[clamp(14px,3vw,36px)] sm:gap-3 sm:px-6">
            <div class="flex items-center gap-1.5">
                <img src="{{ $sectionImage('faq.webp') }}" alt="" loading="lazy" decoding="async"
                    class="-my-2 -ms-1 size-14 flex-none sm:-my-3.5 sm:-ms-1.5 sm:size-20 object-contain">
                <h2 class="min-w-0 text-balance text-[clamp(26px,3.6vw,42px)] font-bold leading-[1.4] text-camp2-ink">
                    {{ __('web.bootcamp.v2.faq.title') }}</h2>
            </div>

            <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,300px),1fr))] gap-2 sm:gap-2.5">
                @foreach ($content['faqs'] as $faq)
                    <div class="flex flex-col gap-1 rounded-[20px] border border-camp2-line bg-white p-3 sm:p-4">
                        <h3 class="text-[19px] font-bold leading-[1.6] text-camp2-ink">{{ $faq['question'] }}</h3>
                        @if ($faq['answer'])
                            <p class="text-camp2-muted">{!! nl2br(e($faq['answer'])) !!}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ==================== SCHEDULE ==================== --}}
    @if ($bootcamp->scheduleWeeks !== [])
        <section id="schedule" class="bg-white border-t border-camp2-line">
            <div class="mx-auto flex max-w-[1120px] flex-col gap-2 px-3 py-[clamp(12px,2.5vw,32px)] sm:gap-3 sm:px-6">
                <div class="flex items-center gap-3">
                    <img src="{{ $sectionImage('schedule.webp') }}" alt="" loading="lazy" decoding="async"
                        class="-my-2 -ms-1 size-16 flex-none sm:-my-3.5 sm:-ms-2 sm:size-[92px] object-contain">
                    <div class="flex flex-col min-w-0 gap-1">
                        <div class="flex items-center gap-1.5 text-[15px] font-semibold text-camp2-brown">
                            <span aria-hidden="true"
                                class="size-[9px] flex-none rotate-45 rounded-[2px] bg-camp2-brown-soft"></span>
                            {{ __('web.bootcamp.v2.schedule.eyebrow') }}
                        </div>
                        <h2 class="text-balance text-[clamp(24px,3.4vw,38px)] font-bold leading-[1.45] text-camp2-ink">
                            {{ $bootcamp->scheduleSummary ?? __('web.bootcamp.schedule.title') }}</h2>
                    </div>
                </div>

                <div class="flex flex-wrap gap-1.5 text-[15px]">
                    @if ($stats['weeks'])
                        <span
                            class="rounded-full bg-camp2-sand-light px-4 py-1.5 font-semibold text-camp2-brown-dark">{{ trans_choice('web.bootcamp.schedule.weeks', $stats['weeks'], ['count' => $stats['weeks']]) }}</span>
                    @endif
                    @if ($stats['sessions'])
                        <span
                            class="rounded-full bg-camp2-sand-light px-4 py-1.5 font-semibold text-camp2-brown-dark">{{ trans_choice('web.bootcamp.schedule.sessions', $stats['sessions'], ['count' => $stats['sessions']]) }}</span>
                    @endif
                    @if ($sessionHours)
                        <span
                            class="rounded-full bg-camp2-sand-light px-4 py-1.5 font-semibold text-camp2-brown-dark">{{ trans_choice('web.bootcamp.v2.schedule.session_hours', $sessionHours, ['count' => $sessionHours]) }}</span>
                    @endif
                    @if ($stats['hours'])
                        <span
                            class="rounded-full bg-camp2-sand-light px-4 py-1.5 font-semibold text-camp2-brown-dark">{{ trans_choice('web.bootcamp.schedule.hours', $stats['hours'], ['count' => $stats['hours']]) }}</span>
                    @endif
                </div>

                <div class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,290px),1fr))] gap-2">
                    @foreach ($bootcamp->scheduleWeeks as $week)
                        <div class="flex flex-col rounded-[20px] bg-camp2-page px-3 py-2 sm:px-5 sm:py-3">
                            <h3 class="mb-1 text-[17px] font-bold text-camp2-brown">{{ $week['label'] }}</h3>
                            @foreach ($week['sessions'] as $session)
                                <div
                                    class="flex gap-3 py-1.5 text-base {{ $loop->first ? '' : 'border-t border-camp2-line' }}">
                                    <span class="w-[62px] flex-none text-camp2-muted">{{ $session['day'] }}</span>
                                    <span
                                        class="font-semibold text-camp2-ink">{{ $session['title'] ?? $session['module'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== FINAL CTA ==================== --}}
    <section class="relative overflow-hidden isolate bg-camp2-slate text-camp2-page">
        <img src="{{ $sectionImage('cta-mark.png') }}" alt="" aria-hidden="true" loading="lazy"
            class="pointer-events-none absolute -end-[90px] top-[39%] -z-10 h-auto w-[300px] max-w-none -translate-y-1/2 opacity-[0.055]">
        <div
            class="mx-auto flex max-w-[1120px] flex-col items-center gap-2 px-3 py-[clamp(16px,3.5vw,44px)] sm:gap-3 sm:px-6 text-center">
            <h2 class="text-balance text-[clamp(28px,4vw,46px)] font-extrabold leading-[1.4] text-white">
                {{ $content['final_cta_title'] ?? $course->title }}</h2>
            @if ($content['final_cta_text'])
                <p class="max-w-[560px] text-[clamp(17px,1.8vw,20px)] text-camp2-mist">
                    {{ $content['final_cta_text'] }}</p>
            @endif
            <a href="#book" class="{{ $ctaClass }}">{{ __('web.bootcamp.book_seat') }}</a>
        </div>
    </section>

    {{-- ==================== RELATED COURSES (same as course-show) ==================== --}}
    @if ($relatedCourses !== [])
        <section class="py-4 bg-white border-t camp-site-font border-camp2-line sm:py-8">
            <div class="flex flex-col items-center gap-3 px-3 sm:gap-4 sm:px-6 lg:px-[68px]">
                <div class="flex flex-col w-full gap-3">
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
    {{-- Hidden while the hero (booking card) is on screen --}}
    <div x-data="{ inView: { about: true }, get show() { return !Object.values(this.inView).some(Boolean) } }" x-init="const io = new IntersectionObserver((entries) => entries.forEach((e) => inView[e.target.id] = e.isIntersecting), { threshold: 0.15 });
    ['about'].forEach((id) => document.getElementById(id) && io.observe(document.getElementById(id)))" x-show="show" x-cloak
        x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full opacity-0"
        x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="translate-y-full opacity-0"
        class="fixed inset-x-0 bottom-0 z-40 flex justify-center border-t border-camp2-line bg-white px-4 py-2.5 md:hidden">
        <a href="#book" class="{{ $ctaClass }} min-h-[50px] w-full">{{ __('web.bootcamp.book_seat') }}</a>
    </div>
</div>
