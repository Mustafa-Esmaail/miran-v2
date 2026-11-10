{{-- âœ… IMPORTANT: Livewire needs ONE root element --}}
<div class="bg-[#f5f5f6] text-neutral-text">
    <style>
        @keyframes skeleton-shimmer {
            0% {
                background-position: -400px 0;
            }

            100% {
                background-position: 400px 0;
            }
        }

        .skeleton {
            background: linear-gradient(90deg, #e5e7eb 25%, #f3f4f6 50%, #e5e7eb 75%);
            background-size: 800px 100%;
            animation: skeleton-shimmer 1.4s ease-in-out infinite;
        }
    </style>
    @php
        $emptyLabel = __('web.common.empty');
    @endphp

    {{-- HERO / HEADER (wrapped properly in a section) --}}
    <section class="relative bg-brand-dark text-white">
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]"
            style="background-image: linear-gradient(rgba(255,255,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 120px 120px;">
        </div>

        <div class="relative flex flex-col items-start gap-8 px-4 pb-0 pt-10 sm:px-6 lg:px-[68px] lg:pt-12">
            <div class="flex w-full flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-2">
                        <h1
                            class="text-2xl font-semibold text-[#f5f5f6] sm:text-3xl lg:text-4xl {{ $isRtl ? 'text-right' : 'text-left' }}">
                            {{ $course->title }}
                        </h1>
                    </div>

                    {{-- <p class="text-sm text-[#c4c4c4] sm:text-base lg:text-lg {{ $isRtl ? 'text-right' : 'text-left' }}">
                        {{ $instructorNamesText ? __('web.course.by', ['name' => $instructorNamesText]) : $emptyLabel }}
                    </p> --}}

                    <div class="flex flex-wrap gap-4">
                        {{-- <div class="flex items-center gap-2 rounded-3xl bg-white px-6 py-2">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                                <path d="M16.5 4H19.5V20H16.5V4ZM4.5 14H7.5V20H4.5V14ZM10.5 9H13.5V20H10.5V9Z"
                                    fill="#86ADAB"></path>
                            </svg>
                            <p class="text-base text-[#242528]">
                                {{ $course->courseLevelLabel ?: ($course->deliveryTypeLabel ?: __('web.courses.level_intermediate')) }}
                            </p>
                        </div> --}}

                        {{-- <div class="flex items-center gap-2 rounded-3xl bg-white px-6 py-2">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="h-6 w-6">
                                <path
                                    d="M16.67 13.13C18.04 14.06 19 15.32 19 17V20H23V17C23 14.82 19.43 13.53 16.67 13.13Z"
                                    fill="#5BBDD2"></path>
                                <path
                                    d="M15 12C17.21 12 19 10.21 19 8C19 5.79 17.21 4 15 4C14.53 4 14.09 4.1 13.67 4.24C14.5 5.27 15 6.58 15 8C15 9.42 14.5 10.73 13.67 11.76C14.09 11.9 14.53 12 15 12Z"
                                    fill="#5BBDD2"></path>
                                <path
                                    d="M9 12C11.21 12 13 10.21 13 8C13 5.79 11.21 4 9 4C6.79 4 5 5.79 5 8C5 10.21 6.79 12 9 12ZM9 6C10.1 6 11 6.9 11 8C11 9.1 10.1 10 9 10C7.9 10 7 9.1 7 8C7 6.9 7.9 6 9 6Z"
                                    fill="#5BBDD2"></path>
                                <path
                                    d="M9 13C6.33 13 1 14.34 1 17V20H17V17C17 14.34 11.67 13 9 13ZM15 18H3V17.01C3.2 16.29 6.3 15 9 15C11.7 15 14.8 16.29 15 17V18Z"
                                    fill="#5BBDD2"></path>
                            </svg>
                            <p class="text-base text-[#242528]">
                                {{ $course->studentsCount }} {{ __('web.course.students_label') }}
                            </p>
                        </div> --}}
                    </div>
                </div>

            </div>

            <div class="grid w-full items-start gap-8 pb-10 lg:grid-cols-12 lg:pb-12">
                <div class="relative h-full  w-full overflow-hidden rounded-3xl bg-black/20  lg:col-span-8 ">
                    @if ($course->introVideoUrl)
                        <video class="h-full w-full object-cover" controls playsinline preload="metadata"
                            poster="{{ $course->imageUrl }}">
                            <source src="{{ $course->introVideoUrl }}" type="video/mp4">
                        </video>
                    @elseif ($course->imageUrl)
                        <img src="{{ $course->imageUrl }}" alt="{{ $course->title }}"
                            class="h-full w-full object-cover">
                    @endif

                    @if ($course->introVideoUrl)
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div
                                class="flex items-center gap-2 rounded-[100px] border border-[#99745c] bg-[#3d3d3d]/[0.04] p-4 backdrop-blur-2xl">
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

                <aside class="lg:col-span-4">

                    <div class="flex flex-col gap-6 rounded-3xl border border-[#ced0d3] bg-white p-6 sm:p-8 lg:p-10">
                        <div class="flex flex-col gap-6">
                            {{-- <p class="text-xl font-semibold text-[#242528]">
                                {{ $sessionCount }} {{ __('web.course.sessions') }}
                                @if ($course->durationHours)
                                    ({{ $course->durationHours }} {{ __('web.course.hours') }})
                                @endif
                            </p> --}}

                            {{-- <div class="flex flex-col gap-3">
                                @forelse ($sessionsPreview as $index => $session)
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-2">
                                            <p class="w-6 text-base text-[#242528]">
                                                {{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</p>
                                            <p class="text-base text-[#242528]">
                                                {{ $session['title'] ?: __('web.course.sessions') }}
                                            </p>
                                        </div>
                                        <p class="text-base text-[#99745c]">
                                            {{ $session['duration_minutes'] ? $session['duration_minutes'] . ' ' . __('web.course.minutes') : '--' }}
                                        </p>
                                    </div>
                                @empty
                                    <p class="text-base text-[#4b4c53]">{{ __('web.course.sessions_empty') }}</p>
                                @endforelse

                                @if ($sessionCount > count($sessionsPreview))
                                    <p class="text-base text-[#4b4c53]">
                                        {{ $sessionCount - count($sessionsPreview) }}
                                        {{ __('web.course.more_videos') }}
                                    </p>
                                @endif
                            </div> --}}
                        </div>

                        <div class="flex flex-col gap-6">
                            {{-- <p class="text-base text-[#4b4c53]">
                                {{ $summaryText }}
                            </p> --}}

                            <div class="flex items-center gap-2">
                                {{-- <div
                                    class="flex items-center gap-2 rounded-3xl border border-[#ced0d3] bg-white px-4 py-2">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                        class="h-5 w-5">
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
                                        class="h-5 w-5">
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
                                        class="h-5 w-5">
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

                            <form wire:submit.prevent="addCourseToCart" class="space-y-4">
                                @if ($course->accreditationNumber)
                                    <p class="text-sm font-semibold text-[#242528]">
                                        {{ __('web.course.accreditation_number') }}:
                                        {{ $course->accreditationNumber }}
                                    </p>
                                @endif

                                <div class="grid grid-cols-3 gap-3">
                                    <input type="number" min="1" wire:model.live="quantity"
                                        class="col-span-1 w-full rounded-3xl border border-[#ced0d3] px-6 py-3 text-base"
                                        placeholder="{{ __('web.course.quantity') }}">
                                    <select wire:model="courseGroupId"
                                        class="col-span-2 w-full rounded-3xl border border-[#ced0d3] px-6 py-3 text-base">
                                        <option value="">{{ __('web.course.select_group') }}</option>
                                        @foreach ($course->groups as $group)
                                            <option value="{{ $group['id'] }}">{{ $group['title'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @error('courseGroupId')
                                    <p class="text-xs text-alert">{{ $message }}</p>
                                @enderror

                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <button type="button" wire:click="buyNow"
                                        class="w-full rounded-3xl bg-[#99745c] px-6 py-3 text-lg font-semibold text-white transition hover:bg-[#86634e] hover:shadow-[0_12px_30px_rgba(153,116,92,0.22)]"
                                        wire:loading.attr="disabled">
                                        {{ __('web.course.buy_now') }}
                                    </button>
                                    <button type="submit"
                                        class="w-full rounded-3xl border border-[#99745c] px-6 py-3 text-lg font-semibold text-[#99745c] transition hover:bg-[#99745c] hover:text-white hover:shadow-[0_12px_30px_rgba(153,116,92,0.18)]"
                                        wire:loading.attr="disabled">
                                        {{ __('web.course.add_to_cart') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- <div class="flex flex-col gap-4">
                            <p class="text-xl font-semibold text-[#242528]">{{ __('web.course.includes') }}</p>
                            <div class="flex flex-col gap-3">
                                @forelse ($keyPoints as $point)
                                    <div class="flex items-start gap-2">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            class="h-6 w-6">
                                            <path
                                                d="M20 6H12L10 4H4C2.9 4 2.01 4.9 2.01 6L2 18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V8C22 6.9 21.1 6 20 6ZM20 18H4V6H9.17L11.17 8H20V18ZM18 12H6V10H18V12ZM14 16H6V14H14V16Z"
                                                fill="#99745C"></path>
                                        </svg>
                                        <p class="text-base text-[#4b4c53]">{{ $point }}</p>
                                    </div>
                                @empty
                                    <p class="text-base text-[#4b4c53]">{{ __('web.course.summary') }}</p>
                                @endforelse
                            </div>
                        </div> --}}

                        <div class="h-px w-full bg-[#d1d1d1]">

                        </div>

                        <div class="flex flex-col gap-3">
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

                        {{-- <div class="flex flex-col gap-6">
                            @forelse ($instructors as $instructor)
                                <div class="flex items-start gap-3">
                                    @if ($instructor['avatar'])
                                        <img src="{{ $instructor['avatar'] }}" alt="{{ $instructor['name'] }}"
                                            class="h-12 w-12 rounded-full object-cover">
                                    @else
                                        <div class="h-12 w-12 rounded-full bg-[#99745c]/20"></div>
                                    @endif
                                    <div>
                                        <p class="text-lg text-[#242528]">{{ $instructor['name'] ?? $emptyLabel }}</p>
                                        <p class="text-base text-[#818181]">{{ __('web.course.instructor_label') }}
                                        </p>
                                    </div>
                                </div>
                                <p class="text-base text-[#242528]">
                                    {{ $instructor['about'] ?? $emptyLabel }}
                                </p>
                            @empty
                                <div class="flex items-start gap-3">
                                    <div class="h-12 w-12 rounded-full bg-[#99745c]/20"></div>
                                    <div>
                                        <p class="text-lg text-[#242528]">{{ __('web.course.instructor_label') }}</p>
                                    </div>
                                </div>
                                <p class="text-base text-[#242528]">{{ $emptyLabel }}</p>
                            @endforelse
                        </div> --}}
                    </div>
                </aside>
            </div>
        </div>
    </section>

    {{-- TABS --}}
    <section class="flex flex-col gap-6 px-4 pb-16 pt-10 sm:px-6 lg:px-[68px] lg:pt-20" x-data="{ activeTab: 'about' }">
        <div class="flex flex-wrap gap-3">
            @foreach ($tabs as $tab)
                <button type="button" class="rounded-3xl px-4 py-3 text-base"
                    :class="activeTab === '{{ $tab['id'] }}' ? 'bg-[#99745c] text-white font-semibold' :
                        'bg-white text-[#242528] font-medium'"
                    @click="activeTab = '{{ $tab['id'] }}'">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        <div class="flex w-full  flex-col gap-6">
            <div x-cloak x-show="activeTab === 'about'" class="flex flex-col gap-6">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-4">
                        {{-- <p class="text-xl font-semibold text-[#242528]">{{ __('web.course.description') }}</p> --}}
                        @if ($timelineItems !== [])
                            <div
                                class="overflow-hidden rounded-3xl bg-white p-5 shadow-[0_2px_9px_rgba(0,0,0,0.07)] sm:p-8">
                                @if (!empty($timelineHighlights))
                                    {{-- Highlights skeleton --}}
                                    <div wire:loading class="grid gap-8 border-b border-[#d5d5d5] pb-8 sm:grid-cols-4">
                                        @for ($i = 0; $i < 4; $i++)
                                            <div class="flex flex-col items-center gap-5">
                                                <div class="skeleton h-24 w-24 rounded-full"></div>
                                                <div class="w-full space-y-2">
                                                    <div class="skeleton mx-auto h-3 w-3/4 rounded"></div>
                                                    <div class="skeleton mx-auto h-3 w-1/2 rounded"></div>
                                                    <div class="skeleton mx-auto h-3 w-2/3 rounded"></div>
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                    {{-- Highlights content --}}
                                    <div wire:loading.remove
                                        class="grid gap-8 border-b border-[#d5d5d5] pb-8 sm:grid-cols-4">
                                        @foreach ($timelineHighlights as $highlight)
                                            <div class="flex flex-col items-center gap-5 text-center">
                                                <div
                                                    class="flex h-24 w-24 items-center justify-center rounded-full border-[3px] border-[#99745c] bg-white shadow-[0_4px_16px_rgba(153,116,92,0.15)]">
                                                    @if (!empty($highlight['icon']))
                                                        <img src="{{ $highlight['icon'] }}" alt=""
                                                            class="h-20 w-20 object-contain">
                                                    @else
                                                        <img src="{{ asset('img/icons/image-' . (($loop->index % 3) + 1) . '.png') }}"
                                                            alt="" class="h-20 w-20 object-contain">
                                                    @endif
                                                </div>
                                                <div
                                                    class="prose prose-sm max-w-none text-sm font-semibold leading-7 text-[#242528]">
                                                    {!! $highlight['text'] !!}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Timeline skeleton --}}
                                <div wire:loading class="relative mt-8 flex flex-col gap-8">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-1/2 hidden w-px -translate-x-1/2 bg-gray-200 lg:block">
                                    </div>
                                    @for ($si = 0; $si < 3; $si++)
                                        <div
                                            class="relative grid items-start grid-cols-[auto_minmax(0,1fr)] gap-4 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:gap-10">
                                            <div
                                                class="skeleton relative z-10 h-12 w-12 shrink-0 self-start rounded-full lg:col-start-2 lg:row-start-1 lg:mx-auto">
                                            </div>
                                            <div
                                                class="{{ $si % 2 === 0 ? 'lg:col-start-3' : 'lg:col-start-1' }} lg:row-start-1 overflow-hidden rounded-3xl shadow-[0_2px_9px_rgba(0,0,0,0.07)]">
                                                <div class="skeleton h-14"></div>
                                                <div class="space-y-2 px-6 py-5">
                                                    <div class="skeleton h-3 w-full rounded"></div>
                                                    <div class="skeleton h-3 w-4/5 rounded"></div>
                                                    <div class="skeleton h-3 w-3/5 rounded"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>
                                {{-- Timeline content --}}
                                <div wire:loading.remove class="relative mt-8 flex flex-col gap-8"
                                    x-data="{ open: null }">
                                    <div
                                        class="pointer-events-none absolute inset-y-0 left-1/2 hidden w-px -translate-x-1/2 bg-[repeating-linear-gradient(to_bottom,#99745c_0_8px,transparent_8px_16px)] lg:block">
                                    </div>

                                    @foreach ($timelineItems as $item)
                                        @php
                                            $isRight = $loop->odd;
                                            $idx = $loop->index;
                                        @endphp
                                        <div
                                            class="relative grid items-start grid-cols-[auto_minmax(0,1fr)] gap-4 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:gap-10">
                                            <div
                                                class="relative z-10 flex h-12 w-12 self-start items-center justify-center overflow-hidden rounded-full border-[3px] border-[#99745c] bg-white lg:col-start-2 lg:row-start-1 lg:mx-auto">
                                                @if ($item['image'])
                                                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] ?? '' }}"
                                                        class="h-8 w-8 rounded-full object-cover">
                                                @else
                                                    <span
                                                        class="text-sm font-bold text-[#99745c]">{{ $loop->iteration }}</span>
                                                @endif
                                            </div>
                                            <div @class([
                                                'overflow-hidden rounded-3xl bg-white shadow-[0_2px_9px_rgba(0,0,0,0.07)]',
                                                'lg:col-start-3' => $isRight,
                                                'lg:col-start-1' => !$isRight,
                                                'lg:row-start-1 lg:self-start' => true,
                                            ])>
                                                <button type="button"
                                                    class="flex w-full items-center justify-between gap-4 bg-[#99745c] px-6 py-4 text-center"
                                                    @click="open = open === {{ $idx }} ? null : {{ $idx }}">
                                                    <p class="flex-1 text-lg font-semibold text-[#fcfcfc]">
                                                        {{ $item['title'] ?: __('web.course.timeline') }}
                                                    </p>
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5 shrink-0 text-white transition-transform duration-300"
                                                        :class="open === {{ $idx }} ? 'rotate-180' : ''"
                                                        viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd"
                                                            d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                                            clip-rule="evenodd" />
                                                    </svg>
                                                </button>
                                                @if ($item['description'])
                                                    <div x-show="open === {{ $idx }}" x-collapse
                                                        class="rich-content px-6 py-5 text-center text-sm leading-7 text-[#424242]">
                                                        {!! $item['description'] !!}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-base leading-relaxed text-[#4b4c53]">{{ $summaryText }}</p>
                        @endif
                        {{-- @if ($course->description)
                            <div class="overflow-hidden rounded-3xl shadow-[0_2px_9px_rgba(0,0,0,0.07)]">
                                <div class="bg-white pb-6">
                                    <div class="bg-[#99745c] px-6 py-[15px] text-center">
                                        <p class="text-xl font-semibold text-[#fcfcfc]">
                                            {{ __('web.course.about') }}
                                        </p>
                                    </div>
                                    <div class="px-8 pt-4">
                                        <div class="rich-content text-center text-base text-[#424242]">
                                            {!! $course->description !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif --}}

                    </div>

                    @if (!empty($sneakImages))
                        <div class="flex flex-col gap-4">
                            <p class="text-xl font-semibold text-[#242528]">{{ __('web.course.sneak_peek') }}</p>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                @foreach ($sneakImages as $image)
                                    <img class="h-[125px] w-full rounded-2xl object-cover" src="{{ $image }}"
                                        alt="">
                                @endforeach
                            </div>
                        </div>
                    @endif


                </div>
            </div>

            <div x-cloak x-show="activeTab === 'lessons'" class="flex flex-col gap-6">
                <div class="flex flex-col gap-3">
                    <p class="text-xl font-semibold text-[#242528]">{{ __('web.course.explore_modules') }}</p>
                    <p class="text-base text-[#4b4c53]">
                        {{ __('web.course.module_intro') }}
                    </p>
                </div>

                <div class="flex flex-col gap-3">
                    <p class="text-xl font-semibold text-[#242528]">{{ __('web.course.lesson_list') }}</p>
                    <div class="flex flex-col gap-6">
                        @forelse (array_slice($course->sessions, 0, 6) as $index => $session)
                            <div class="flex items-center gap-[13px]">
                                <div class="rounded-3xl bg-[#99745c] p-4">
                                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none"
                                        class="h-10 w-10">
                                        <path
                                            d="M25 13.3333V26.6667H8.33333V13.3333H25ZM26.6667 10H6.66667C5.75 10 5 10.75 5 11.6667V28.3333C5 29.25 5.75 30 6.66667 30H26.6667C27.5833 30 28.3333 29.25 28.3333 28.3333V22.5L35 29.1667V10.8333L28.3333 17.5V11.6667C28.3333 10.75 27.5833 10 26.6667 10Z"
                                            fill="white"></path>
                                    </svg>
                                </div>

                                <div class="flex flex-1 flex-col gap-1">
                                    <p class="text-base font-semibold text-[#242528]">
                                        {{ __('web.course.module_label', ['number' => $index + 1]) }}:
                                        {{ $session['title'] ?: __('web.course.sessions') }}
                                    </p>
                                    <p class="text-base text-[#818181]">
                                        {{ $summaryText }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <p class="text-base text-[#4b4c53]">{{ __('web.course.sessions_empty') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div x-cloak x-show="activeTab === 'questions'" class="flex flex-col gap-6">
                <livewire:courses.questions :course-id="$course->id" />
            </div>

            <div x-cloak x-show="activeTab === 'reviews'" class="flex flex-col gap-6">
                <div class="flex flex-col gap-3">
                    <p class="text-xl font-semibold text-[#242528]">{{ __('web.course.what_learners_saying') }}</p>
                    <p class="text-base text-[#4b4c53]">
                        {{ __('web.course.reviews_intro', ['course' => $course->title]) }}
                    </p>
                </div>

                <div class="flex flex-col gap-6 rounded-[20px] border border-[#ced0d3] bg-white p-6">
                    <div class="flex flex-col gap-2">
                        <p class="text-xl font-semibold text-[#000a19]">{{ __('web.course.overall_rating') }}</p>
                        <p class="text-sm font-medium text-[#868686]">
                            {{ $course->reviewsCount }} {{ __('web.courses.reviews') }}
                        </p>
                    </div>

                    <div class="flex flex-col items-center gap-6 rounded-2xl border border-[#ebebeb] bg-[#f8f8f8] p-6">
                        <div class="flex flex-col items-center gap-3">
                            <p class="text-left">
                                <span class="text-[48px] font-semibold text-[#000a19] sm:text-[56px]">
                                    {{ number_format($course->ratingAverage ?? 0, 1) }}
                                </span>
                                <span class="text-xl font-medium text-[#727a90]">/5.0</span>
                            </p>
                            <div class="flex items-center gap-1">
                                @foreach ($ratingStars as $star)
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        class="h-6 w-6">
                                        <path
                                            d="M18.4834 16.7674C17.8471 16.9195 17.1829 17 16.5 17C11.8056 17 8 13.1944 8 8.50001C8 8.01653 8.04036 7.54249 8.11791 7.08105C8.08172 7.11586 8.04432 7.14792 8.00494 7.17781C7.72433 7.39083 7.37485 7.46991 6.67589 7.62806L6.03954 7.77204C3.57986 8.32856 2.35002 8.60682 2.05742 9.54774C1.76482 10.4887 2.60325 11.4691 4.2801 13.4299L4.71392 13.9372C5.19042 14.4944 5.42868 14.773 5.53586 15.1177C5.64305 15.4624 5.60703 15.8341 5.53498 16.5776L5.4694 17.2544C5.21588 19.8706 5.08912 21.1787 5.85515 21.7602C6.62117 22.3417 7.77267 21.8116 10.0757 20.7512L10.6715 20.4768C11.3259 20.1755 11.6531 20.0249 12 20.0249C12.3469 20.0249 12.6741 20.1755 13.3285 20.4768L13.9243 20.7512C16.2273 21.8116 17.3788 22.3417 18.1449 21.7602C18.9109 21.1787 18.7841 19.8706 18.5306 17.2544L18.4834 16.7674Z"
                                            fill="{{ $star['fill'] }}"></path>
                                        <path opacity="0.5"
                                            d="M9.15302 5.40838L8.82532 5.99623C8.46538 6.64194 8.28541 6.96479 8.0048 7.17781C8.04418 7.14791 8.08158 7.11586 8.11777 7.08105C8.04022 7.54249 7.99986 8.01653 7.99986 8.50001C7.99986 13.1944 11.8054 17 16.4999 17C17.1828 17 17.8469 16.9195 18.4833 16.7674L18.4649 16.5776C18.3928 15.8341 18.3568 15.4624 18.464 15.1177C18.5712 14.773 18.8094 14.4944 19.2859 13.9372L19.7198 13.4299C21.3966 11.4691 22.235 10.4886 21.9424 9.54773C21.6498 8.60682 20.42 8.32856 17.9603 7.77203L17.324 7.62805C16.625 7.4699 16.2755 7.39083 15.9949 7.17781C15.7143 6.96479 15.5343 6.64194 15.1744 5.99624L14.8467 5.40837C13.58 3.13612 12.9467 2 11.9999 2C11.053 2 10.4197 3.13613 9.15302 5.40838Z"
                                            fill="{{ $star['fill'] }}"></path>
                                    </svg>
                                @endforeach
                            </div>
                        </div>
                        <p class="text-center text-sm text-[#868686]">
                            {{ __('web.course.reviews_based_on') }}
                            <span class="font-bold text-[#000a19]">{{ $course->satisfactionPercent }}%</span>
                        </p>
                    </div>

                    <div class="flex flex-col gap-4">
                        @foreach ($course->ratingBreakdown as $row)
                            <div class="flex items-center gap-2">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    class="h-6 w-6">
                                    <path
                                        d="M18.4834 16.7674C17.8471 16.9195 17.1829 17 16.5 17C11.8056 17 8 13.1944 8 8.50001C8 8.01653 8.04036 7.54249 8.11791 7.08105C8.08172 7.11586 8.04432 7.14792 8.00494 7.17781C7.72433 7.39083 7.37485 7.46991 6.67589 7.62806L6.03954 7.77204C3.57986 8.32856 2.35002 8.60682 2.05742 9.54774C1.76482 10.4887 2.60325 11.4691 4.2801 13.4299L4.71392 13.9372C5.19042 14.4944 5.42868 14.773 5.53586 15.1177C5.64305 15.4624 5.60703 15.8341 5.53498 16.5776L5.4694 17.2544C5.21588 19.8706 5.08912 21.1787 5.85515 21.7602C6.62117 22.3417 7.77267 21.8116 10.0757 20.7512L10.6715 20.4768C11.3259 20.1755 11.6531 20.0249 12 20.0249C12.3469 20.0249 12.6741 20.1755 13.3285 20.4768L13.9243 20.7512C16.2273 21.8116 17.3788 22.3417 18.1449 21.7602C18.9109 21.1787 18.7841 19.8706 18.5306 17.2544L18.4834 16.7674Z"
                                        fill="#E0AF02"></path>
                                    <path opacity="0.5"
                                        d="M9.15302 5.40838L8.82532 5.99623C8.46538 6.64194 8.28541 6.96479 8.0048 7.17781C8.04418 7.14791 8.08158 7.11586 8.11777 7.08105C8.04022 7.54249 7.99986 8.01653 7.99986 8.50001C7.99986 13.1944 11.8054 17 16.4999 17C17.1828 17 17.8469 16.9195 18.4833 16.7674L18.4649 16.5776C18.3928 15.8341 18.3568 15.4624 18.464 15.1177C18.5712 14.773 18.8094 14.4944 19.2859 13.9372L19.7198 13.4299C21.3966 11.4691 22.235 10.4886 21.9424 9.54773C21.6498 8.60682 20.42 8.32856 17.9603 7.77203L17.324 7.62805C16.625 7.4699 16.2755 7.39083 15.9949 7.17781C15.7143 6.96479 15.5343 6.64194 15.1744 5.99624L14.8467 5.40837C13.58 3.13612 12.9467 2 11.9999 2C11.053 2 10.4197 3.13613 9.15302 5.40838Z"
                                        fill="#E0AF02"></path>
                                </svg>
                                <p class="w-8 text-sm font-semibold text-[#868686]">
                                    {{ number_format($row['rating'], 1) }}</p>
                                <div class="flex h-2 flex-1 overflow-hidden rounded-full bg-[#ebebeb]">
                                    <div class="h-full rounded-full bg-[#f6c002]"
                                        style="width: {{ $row['percent'] }}%;"></div>
                                </div>
                                <p class="w-12 text-right text-sm font-semibold text-[#000a19]">{{ $row['count'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <p class="text-xl font-semibold text-[#242528]">{{ __('web.course.individual_reviews') }}</p>
                    <div class="flex flex-wrap gap-4">
                        <div class="rounded-3xl bg-[#99745c] px-4 py-3 text-base font-medium text-white">
                            {{ __('web.reviews.all_rating') }}</div>
                        @foreach ([5, 4, 3, 2, 1] as $rating)
                            <div class="flex items-center gap-1 rounded-3xl bg-white px-4 py-3">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    class="h-6 w-6">
                                    <path
                                        d="M18.4834 16.7674C17.8471 16.9195 17.1829 17 16.5 17C11.8056 17 8 13.1944 8 8.50001C8 8.01653 8.04036 7.54249 8.11791 7.08105C8.08172 7.11586 8.04432 7.14792 8.00494 7.17781C7.72433 7.39083 7.37485 7.46991 6.67589 7.62806L6.03954 7.77204C3.57986 8.32856 2.35002 8.60682 2.05742 9.54774C1.76482 10.4887 2.60325 11.4691 4.2801 13.4299L4.71392 13.9372C5.19042 14.4944 5.42868 14.773 5.53586 15.1177C5.64305 15.4624 5.60703 15.8341 5.53498 16.5776L5.4694 17.2544C5.21588 19.8706 5.08912 21.1787 5.85515 21.7602C6.62117 22.3417 7.77267 21.8116 10.0757 20.7512L10.6715 20.4768C11.3259 20.1755 11.6531 20.0249 12 20.0249C12.3469 20.0249 12.6741 20.1755 13.3285 20.4768L13.9243 20.7512C16.2273 21.8116 17.3788 22.3417 18.1449 21.7602C18.9109 21.1787 18.7841 19.8706 18.5306 17.2544L18.4834 16.7674Z"
                                        fill="#E0AF02"></path>
                                    <path opacity="0.5"
                                        d="M9.15302 5.40838L8.82532 5.99623C8.46538 6.64194 8.28541 6.96479 8.0048 7.17781C8.04418 7.14791 8.08158 7.11586 8.11777 7.08105C8.04022 7.54249 7.99986 8.01653 7.99986 8.50001C7.99986 13.1944 11.8054 17 16.4999 17C17.1828 17 17.8469 16.9195 18.4833 16.7674L18.4649 16.5776C18.3928 15.8341 18.3568 15.4624 18.464 15.1177C18.5712 14.773 18.8094 14.4944 19.2859 13.9372L19.7198 13.4299C21.3966 11.4691 22.235 10.4886 21.9424 9.54773C21.6498 8.60682 20.42 8.32856 17.9603 7.77203L17.324 7.62805C16.625 7.4699 16.2755 7.39083 15.9949 7.17781C15.7143 6.96479 15.5343 6.64194 15.1744 5.99624L14.8467 5.40837C13.58 3.13612 12.9467 2 11.9999 2C11.053 2 10.4197 3.13613 9.15302 5.40838Z"
                                        fill="#E0AF02"></path>
                                </svg>
                                <p class="text-base font-medium text-[#4b4c53]">{{ $rating }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-col gap-6">
                    <div class="rounded-3xl border border-[#ced0d3] bg-white p-6 sm:p-10">
                        <p class="text-lg font-semibold text-[#242528]">{{ __('web.course.review_form_title') }}</p>
                        @auth
                            @if ($canReview)
                                <form wire:submit.prevent="submitReview" class="mt-4 flex flex-col gap-4">
                                    <div class="flex flex-col gap-2">
                                        <label
                                            class="text-sm font-medium text-[#4b4c53]">{{ __('web.course.rating_label') }}</label>
                                        <select wire:model="reviewRating"
                                            class="w-full rounded-2xl border border-[#ced0d3] px-4 py-2 text-base">
                                            @for ($rating = 5; $rating >= 1; $rating--)
                                                <option value="{{ $rating }}">{{ $rating }}</option>
                                            @endfor
                                        </select>
                                        @error('reviewRating')
                                            <p class="text-xs text-alert">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <textarea wire:model="reviewContent"
                                            class="min-h-[120px] w-full rounded-2xl border border-[#ced0d3] px-4 py-3 text-base"
                                            placeholder="{{ __('web.course.review_placeholder') }}"></textarea>
                                        @error('reviewContent')
                                            <p class="text-xs text-alert">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <button type="submit"
                                        class="w-full rounded-3xl bg-[#99745c] px-6 py-3 text-base font-semibold text-white">
                                        {{ __('web.course.review_submit') }}
                                    </button>
                                </form>
                            @else
                                <p class="mt-3 text-base text-[#4b4c53]">{{ __('web.course.review_not_allowed') }}</p>
                            @endif
                        @else
                            <p class="mt-3 text-base text-[#4b4c53]">{{ __('web.course.review_login_prompt') }}</p>
                            <a href="{{ route('login') }}"
                                class="mt-3 inline-flex items-center justify-center rounded-3xl bg-[#99745c] px-6 py-2 text-sm font-semibold text-white">
                                {{ __('web.course.review_login_cta') }}
                            </a>
                        @endauth
                    </div>

                    @forelse ($reviews as $review)
                        <div class="flex flex-col gap-3 rounded-3xl border border-[#ced0d3] bg-white p-6 sm:p-10">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-3">
                                        @if (!empty($review['avatar']))
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                src="{{ $review['avatar'] }}" alt="{{ $review['name'] }}">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-[#99745c]/20"></div>
                                        @endif
                                        <p class="text-lg font-medium text-[#242528]">{{ $review['name'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                                class="h-5 w-5">
                                                <path
                                                    d="M18.4834 16.7674C17.8471 16.9195 17.1829 17 16.5 17C11.8056 17 8 13.1944 8 8.50001C8 8.01653 8.04036 7.54249 8.11791 7.08105C8.08172 7.11586 8.04432 7.14792 8.00494 7.17781C7.72433 7.39083 7.37485 7.46991 6.67589 7.62806L6.03954 7.77204C3.57986 8.32856 2.35002 8.60682 2.05742 9.54774C1.76482 10.4887 2.60325 11.4691 4.2801 13.4299L4.71392 13.9372C5.19042 14.4944 5.42868 14.773 5.53586 15.1177C5.64305 15.4624 5.60703 15.8341 5.53498 16.5776L5.4694 17.2544C5.21588 19.8706 5.08912 21.1787 5.85515 21.7602C6.62117 22.3417 7.77267 21.8116 10.0757 20.7512L10.6715 20.4768C11.3259 20.1755 11.6531 20.0249 12 20.0249C12.3469 20.0249 12.6741 20.1755 13.3285 20.4768L13.9243 20.7512C16.2273 21.8116 17.3788 22.3417 18.1449 21.7602C18.9109 21.1787 18.7841 19.8706 18.5306 17.2544L18.4834 16.7674Z"
                                                    fill="{{ ($review['rating'] ?? 0) >= $i ? '#E0AF02' : '#D3D3D3' }}">
                                                </path>
                                            </svg>
                                        @endfor
                                    </div>
                                </div>
                                <p class="text-base text-[#4b4c53]">{{ $review['created_at'] }}</p>
                            </div>
                            <p class="text-base text-[#4b4c53]">
                                {{ $review['content'] }}
                            </p>
                        </div>
                    @empty
                        <p class="text-base text-[#4b4c53]">{{ __('web.course.reviews_empty') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- RELATED --}}
    <div class="flex flex-col items-center gap-12 px-4 pb-20 sm:px-6 lg:px-[68px]">
        <div class="flex w-full flex-col gap-8">
            <div class="flex items-center justify-center">
                <p class="text-[32px] font-bold text-[#322f37]">{{ __('web.course.interested_title') }}</p>
            </div>

            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($relatedCourses as $related)
                    <x-course-card :card="$related" />
                @empty
                    <p class="text-base text-[#4b4c53]">{{ __('web.courses.empty') }}</p>
                @endforelse
            </div>
        </div>
    </div>



</div>
