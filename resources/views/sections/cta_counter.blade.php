@php
    $locale = app()->getLocale();

    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $getValue = fn(string $key, $default = null) => $section->fieldValue($key, $default);

    $title = $getText('title', $section->title ?? '');
    $subtitle = $getText('subtitle', $section->subtitle ?? '');

    $backgroundImage = $getValue('background_image', null);
    $backgroundImage = $backgroundImage
        ? (str_starts_with($backgroundImage, 'http')
            ? $backgroundImage
            : \App\Support\S3Url::temporary($backgroundImage))
        : null;

    $targetDateRaw = $getValue('target_date');
    $targetDate = $targetDateRaw ? \Illuminate\Support\Carbon::parse($targetDateRaw, config('app.timezone')) : null;
    $targetDateIso = $targetDate?->toIso8601String();
    $targetDateLabel = $targetDate?->translatedFormat('d F Y');
@endphp

@if ($targetDate)
    <section
        class="relative overflow-hidden bg-cover bg-center py-2 text-white full-bleed{{ $backgroundImage ? '' : ' bg-brand-dark' }}"
        @if ($backgroundImage) style="background-image: url('{{ $backgroundImage }}');" @endif
        x-data="ctaCounter('{{ $targetDateIso }}')"
        x-init="start()"
    >
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]">
            <div class="h-full border-white border-x"></div>
        </div>

        <div class="relative flex flex-col items-center gap-2 px-4 text-center app-container lg:flex-row lg:justify-center lg:gap-4">
            @if ($title)
                <p class="max-w-xs text-sm font-semibold text-white sm:text-base sm:max-w-sm ">{{ $title }}</p>
            @endif

            <div class="flex flex-col items-center w-full max-w-md gap-1">
                @if ($subtitle)
                    <p class="hidden text-base text-white sm:block sm:text-base">{{ $subtitle }}</p>
                @endif

                <div class="flex items-center justify-center gap-1.5 rounded-xl bg-black/10 p-1.5 sm:gap-2 sm:p-2">
                    <div class="flex flex-col items-center">
                        <p class="text-xs sm:text-base" x-text="days"></p>
                        <p class="text-[11px] text-white">{{ __('web.common.countdown.days') }}</p>
                    </div>
                    <span class="h-2 w-2 shrink-0 rounded-full bg-[#D9D9D9]"></span>
                    <div class="flex flex-col items-center">
                        <p class="text-xs sm:text-base" x-text="hours"></p>
                        <p class="text-[11px] text-white">{{ __('web.common.countdown.hours') }}</p>
                    </div>
                    <span class="h-2 w-2 shrink-0 rounded-full bg-[#D9D9D9]"></span>
                    <div class="flex flex-col items-center">
                        <p class="text-xs sm:text-base" x-text="minutes"></p>
                        <p class="text-[11px] text-white">{{ __('web.common.countdown.minutes') }}</p>
                    </div>
                    <span class="h-2 w-2 shrink-0 rounded-full bg-[#D9D9D9]"></span>
                    <div class="flex flex-col items-center">
                        <p class="text-xs sm:text-base" x-text="seconds"></p>
                        <p class="text-[11px] text-white">{{ __('web.common.countdown.seconds') }}</p>
                    </div>
                </div>
            </div>

            @if ($targetDateLabel)
                <div class="flex-col items-center hidden gap-1 sm:flex">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6">
                        <path d="M16.7502 3.56V2C16.7502 1.59 16.4102 1.25 16.0002 1.25C15.5902 1.25 15.2502 1.59 15.2502 2V3.5H8.75023V2C8.75023 1.59 8.41023 1.25 8.00023 1.25C7.59023 1.25 7.25023 1.59 7.25023 2V3.56C4.55023 3.81 3.24023 5.42 3.04023 7.81C3.02023 8.1 3.26023 8.34 3.54023 8.34H20.4602C20.7502 8.34 20.9902 8.09 20.9602 7.81C20.7602 5.42 19.4502 3.81 16.7502 3.56Z" fill="white" />
                        <path opacity="0.4" d="M20 9.84009C20.55 9.84009 21 10.2901 21 10.8401V17.0001C21 20.0001 19.5 22.0001 16 22.0001H8C4.5 22.0001 3 20.0001 3 17.0001V10.8401C3 10.2901 3.45 9.84009 4 9.84009H20Z" fill="white" />
                    </svg>
                    <p class="text-base text-white">{{ $targetDateLabel }}</p>
                </div>
            @endif
        </div>
    </section>

    @once('cta-counter-script')
        <script>
            function ctaCounter(targetDateIso) {
                return {
                    days: '00',
                    hours: '00',
                    minutes: '00',
                    seconds: '00',
                    timer: null,
                    start() {
                        const targetTime = new Date(targetDateIso).getTime();
                        const pad = (n) => String(n).padStart(2, '0');
                        const tick = () => {
                            const diff = targetTime - Date.now();
                            if (diff <= 0) {
                                this.days = this.hours = this.minutes = this.seconds = '00';
                                clearInterval(this.timer);
                                return;
                            }
                            this.days = pad(Math.floor(diff / 86400000));
                            this.hours = pad(Math.floor((diff % 86400000) / 3600000));
                            this.minutes = pad(Math.floor((diff % 3600000) / 60000));
                            this.seconds = pad(Math.floor((diff % 60000) / 1000));
                        };
                        tick();
                        this.timer = setInterval(tick, 1000);
                    },
                }
            }
        </script>
    @endonce
@endif
