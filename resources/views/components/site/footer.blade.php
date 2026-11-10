<footer class="relative overflow-hidden border-t border-[#ced0d3] bg-white text-[#242528]">
    <svg width="685" height="442" viewBox="0 0 685 442" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute left-[-493px] top-2 hidden lg:block" preserveAspectRatio="none">
        <g filter="url(#footerGlowBottom)">
            <circle cx="76.5" cy="577.5" r="568.5" fill="url(#footerGlowBottomGradient)" fill-opacity="0.24" />
        </g>
        <defs>
            <filter id="footerGlowBottom" x="-532" y="-31" width="1217" height="1217" filterUnits="userSpaceOnUse"
                color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix" />
                <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                <feGaussianBlur stdDeviation="20" result="effect1_foregroundBlur_2005_5077" />
            </filter>
            <radialGradient id="footerGlowBottomGradient" cx="0" cy="0" r="1"
                gradientUnits="userSpaceOnUse" gradientTransform="translate(76.5 577.5) rotate(90) scale(568.5)">
                <stop stop-color="#C0945B" />
                <stop offset="0.53" stop-color="#C0945B" stop-opacity="0.23" />
                <stop offset="0.75" stop-color="#C0945B" stop-opacity="0.06" />
                <stop offset="1" stop-color="#C0945B" stop-opacity="0" />
            </radialGradient>
        </defs>
    </svg>

    <svg width="528" height="442" viewBox="0 0 528 442" fill="none" xmlns="http://www.w3.org/2000/svg"
        class="pointer-events-none absolute right-[-230px] top-[-320px] hidden lg:block" preserveAspectRatio="none">
        <g filter="url(#footerGlowTop)">
            <circle cx="608.5" cy="-112.5" r="568.5" fill="url(#footerGlowTopGradient)" fill-opacity="0.4" />
        </g>
        <defs>
            <filter id="footerGlowTop" x="0" y="-721" width="1217" height="1217" filterUnits="userSpaceOnUse"
                color-interpolation-filters="sRGB">
                <feFlood flood-opacity="0" result="BackgroundImageFix" />
                <feBlend mode="normal" in="SourceGraphic" in2="BackgroundImageFix" result="shape" />
                <feGaussianBlur stdDeviation="20" result="effect1_foregroundBlur_2005_5155" />
            </filter>
            <radialGradient id="footerGlowTopGradient" cx="0" cy="0" r="1"
                gradientUnits="userSpaceOnUse" gradientTransform="translate(608.5 -112.5) rotate(90) scale(568.5)">
                <stop stop-color="#719693" />
                <stop offset="0.53" stop-color="#719693" stop-opacity="0.23" />
                <stop offset="0.75" stop-color="#719693" stop-opacity="0.06" />
                <stop offset="1" stop-color="#719693" stop-opacity="0" />
            </radialGradient>
        </defs>
    </svg>

    <div class="relative mx-auto flex w-full max-w-[1440px] flex-col gap-8 px-4 py-8 sm:px-6 lg:px-[68px]">
        <div class="grid gap-6 lg:grid-cols-4">

            <div class="lg:col-span-3 min-w-0" x-data="miranSlider(3, 4000)">
                <div class="overflow-hidden">
                    <div x-ref="track" class="flex gap-6 transition-[transform] duration-500 ease-in-out">
                        @foreach ($footerCertificationCards as $card)
                            <div class="p-5 text-center">
                                @if (!empty($card['logo']))
                                    <img src="{{ $card['logo'] }}" alt="{{ $card['label'] }}"
                                        class="mx-auto mt-3 h-30 w-auto object-contain">
                                @endif
                                <p class="text-xs font-medium text-[#242528]">
                                    {{ $card['label'] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div x-show="_max > 0" class="mt-3 flex justify-center gap-2">
                    <template x-for="dot in _dots" :key="dot">
                        <button @click="active = dot; _apply(); _resetTimer()"
                            class="h-2 rounded-full bg-[#99745c] transition-all duration-300"
                            :class="_page === dot ? 'w-6 opacity-100' : 'w-2 opacity-30'">
                        </button>
                    </template>
                </div>
            </div>
            <div class=" p-5 flex justify-center  text-center ">
                {{-- <p class="mb-4 text-base font-bold text-[#242528]">{{ __('web.footer.browse') }}</p> --}}
                <div @class([
                    ' text-sm text-[#242528] flex lg:flex-col flex-row gap-2 lg:gap-1 ',
                    'text-right' => $isRtl,
                ])>
                    @forelse ($footerBrowseLinks as $link)
                        <a class="block transition-colors hover:text-[#99745c]" href="{{ $link['href'] }}"
                            @if (!empty($link['new_tab'])) target="_blank" rel="noopener noreferrer" @endif>
                            {{ $link['label'] }}
                        </a>
                    @empty
                        @foreach ($browseFallback as $item)
                            <p>{{ $item }}</p>
                        @endforeach
                    @endforelse
                </div>
            </div>
        </div>

        <div class="border-t border-[#cbcbcb] pt-6">
            <div class="app-container ">


                <div class=" flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <p class="text-sm text-[#3e3e59]">&copy; {{ $year }} Miran. {{ __('web.layout.footer') }}
                    </p>

                    <div class="flex flex-wrap items-center gap-3">
                        @foreach ($paymentMethodLogos as $method)
                            @if (!empty($method['logo']))
                                <img src="{{ $method['logo'] }}" alt="{{ $method['label'] }}"
                                    class="h-7 w-auto object-contain">
                            @endif
                        @endforeach


                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="ms-2 text-sm text-[#242528]">{{ __('web.footer.follow_us') }}</span>
                        @foreach ($socialLinks as $link)
                            @php
                                $iconComponent = 'icon.' . ($link['icon'] ?? 'linkedin');
                                $iconView = 'components.' . $iconComponent;
                                $fallbackComponent = 'icon.globe';
                            @endphp
                            <a href="{{ $link['href'] }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[#99745c] text-[#99745c] transition-colors hover:bg-[#99745c] hover:text-white"
                                aria-label="{{ $link['label'] }}"
                                @if (!empty($link['new_tab'])) target="_blank" rel="noopener noreferrer" @endif>
                                @if (view()->exists($iconView))
                                    <x-dynamic-component :component="$iconComponent" class="h-4 w-4" />
                                @else
                                    <x-dynamic-component :component="$fallbackComponent" class="h-4 w-4" />
                                @endif
                            </a>
                        @endforeach

                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-4 text-xs text-[#242528]">
                    @forelse ($footerPolicyLinks as $link)
                        <a class="transition-colors hover:text-[#99745c]" href="{{ $link['url'] }}">
                            {{ $link['label'] }}
                        </a>
                    @empty
                        <p>{{ __('web.footer.policy_privacy') }}</p>
                        <p>{{ __('web.footer.policy_terms') }}</p>
                        <p>{{ __('web.footer.policy_refund') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>


</footer>
