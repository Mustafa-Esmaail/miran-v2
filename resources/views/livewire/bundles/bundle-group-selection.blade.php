<div class="pb-10 bg-[#f5f5f6] text-neutral-text">
    @php
        $isRtl = app()->getLocale() === 'ar';
        $bundleTitle = $bundle->getTranslation('title', app()->getLocale()) ?? '';
    @endphp

    {{-- HERO / HEADER --}}
    <section class="relative bg-brand-dark text-white">
        <div class="pointer-events-none absolute inset-0 opacity-[0.12]"
            style="background-image: linear-gradient(rgba(255,255,255,0.4) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.4) 1px, transparent 1px); background-size: 120px 120px;">
        </div>

        <div class="relative flex flex-col items-start gap-8 px-4 pb-0 pt-10 sm:px-6 lg:px-[68px] lg:pt-12">
            <div class="flex w-full flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-col gap-2">
                        <p
                            class="text-2xl font-semibold text-[#f5f5f6] sm:text-3xl lg:text-4xl {{ $isRtl ? 'text-right' : 'text-left' }}">
                            {{ $bundleTitle }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2.5">
                        <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold backdrop-blur">
                            {{ $bundle->category?->getTranslation('name', app()->getLocale()) ?? __('web.common.empty') }}
                        </span>
                        <span class="rounded-full bg-white/15 px-3 py-1.5 text-xs font-semibold backdrop-blur">
                            {{ __('web.bundles.course_count', ['count' => count($courses)]) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid w-full items-start gap-8 pb-10 lg:grid-cols-12 lg:pb-12">
                <div class="relative h-64 w-full overflow-hidden rounded-3xl bg-black/20 sm:h-80 lg:col-span-8 lg:h-[479px]">
                    <img src="{{ $bundle->image_url ?? 'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg' }}"
                        alt="{{ $bundleTitle }}" class="h-full w-full object-cover">
                </div>

                <aside class="lg:col-span-4">
                    <div
                        class="flex flex-col gap-6 rounded-3xl border border-[#ced0d3] bg-white p-6 sm:p-8 lg:p-10">
                        @if (!empty($bundlePrice) && (float) $bundlePrice > 0)
                            <div class="flex flex-col gap-4">
                                <div class="flex items-center gap-1.5 text-[#99745c]">
                                    <x-icon.currency class="h-6 w-6" />
                                    <p class="text-2xl font-semibold sm:text-3xl lg:text-4xl">
                                        {{ number_format((float) $bundlePrice, 2) }}
                                    </p>
                                </div>

                                <x-tamara-widget
                                    :amount="$bundlePrice"
                                    :currency="$siteCurrency"
                                    :publicKey="$tamaraPublicKey"
                                />
                            </div>
                        @endif

                        <div class="flex flex-col gap-4">
                            <p class="text-sm font-semibold text-[#242528]">
                                {{ __('web.bundles.groups.title') }}
                            </p>

                            @foreach ($courses as $course)
                                <div class="{{ ! $loop->last ? 'border-b border-[#ced0d3] pb-4' : '' }}">
                                    <label class="mb-2 block text-xs font-semibold text-[#242528]">
                                        {{ $course['title'] }}
                                    </label>

                                    @if ($course['groups'] === [])
                                        <p class="text-xs text-alert">
                                            {{ __('web.bundles.groups.no_available_groups') }}
                                        </p>
                                    @else
                                        <select wire:model="selectedGroups.{{ $course['id'] }}"
                                            class="w-full rounded-3xl border border-[#ced0d3] px-6 py-3 text-base">
                                            <option value="">{{ __('web.bundles.groups.select_group') }}</option>
                                            @foreach ($course['groups'] as $group)
                                                <option value="{{ $group['id'] }}">{{ $group['title'] }}</option>
                                            @endforeach
                                        </select>
                                    @endif

                                    @error('selectedGroups.' . $course['id'])
                                        <p class="mt-1.5 text-xs text-alert">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <a href="{{ route('bundles.index') }}"
                                class="inline-flex items-center justify-center rounded-3xl border border-[#99745c] px-6 py-3 text-base font-semibold text-[#99745c] transition hover:bg-[#99745c] hover:text-white hover:shadow-[0_12px_30px_rgba(153,116,92,0.18)]">
                                {{ __('web.bundles.groups.back') }}
                            </a>

                            <button type="button" wire:click="confirmAddToCart"
                                class="inline-flex items-center justify-center rounded-3xl bg-[#99745c] px-6 py-3 text-base font-semibold text-white transition hover:bg-[#86634e] hover:shadow-[0_12px_30px_rgba(153,116,92,0.22)]"
                                wire:loading.attr="disabled">
                                {{ __('web.bundles.groups.add_to_cart') }}
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <div class="app-container pb-8 pt-10 sm:pb-10">
        <div class="mb-6">
            <h2 class="text-xl font-semibold text-primary sm:text-2xl">
                {{ __('web.bundles.groups.subtitle', ['bundle' => $bundleTitle]) }}
            </h2>
        </div>

        <div class="space-y-4">
            @foreach ($courses as $course)
                <div class="overflow-hidden rounded-2xl border border-default bg-surface">
                    <div class="grid gap-0 sm:grid-cols-[220px_1fr]">
                        <div class="h-40 bg-[#e9dfd9] sm:h-full">
                            <img src="{{ $course['image_url'] ?? 'https://themiran.net/wp-content/uploads/2024/01/photo_5809831420288417545_y.jpg' }}"
                                alt="{{ $course['title'] }}" class="h-full w-full object-cover">
                        </div>
                        <div class="space-y-2 p-4 sm:p-5">
                            <h3 class="text-base font-semibold text-primary sm:text-lg">
                                {{ $course['title'] }}
                            </h3>
                            <div class="line-clamp-3 text-sm text-secondary">
                                {!! $course['summary'] !== '' ? $course['summary'] : __('web.common.empty') !!}
                            </div>
                            <a href="{{ route('courses.show', $course['slug']) }}"
                                class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold text-[#99745c] hover:underline">
                                {{ __('web.bundles.view_course') }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 {{ $isRtl ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>