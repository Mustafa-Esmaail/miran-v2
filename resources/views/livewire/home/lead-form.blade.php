@php
    $locale = app()->getLocale();
    $layout = $this->fieldValue('layout', 'default');
    $tag = $this->fieldText('tag', $locale, __('web.home.lead_tag'));
    $title = $this->fieldText('heading', $locale, __('web.home.lead_title_landing'));
    $subtitle = $this->fieldText('description', $locale, __('web.home.lead_subtitle'));
    $nameLabel = $this->fieldText('field_name', $locale, __('web.home.lead_name'));
    $phoneLabel = $this->fieldText('field_phone', $locale, __('web.home.lead_phone'));
    $courseLabel = $this->fieldText('field_course', $locale, __('web.home.lead_course'));
    $messageLabel = $this->fieldText('field_message', $locale, __('web.home.lead_message'));
    $submitLabel = $this->fieldText('submit_text', $locale, __('web.home.lead_submit'));
    $note = $this->fieldText('note', $locale, __('web.home.lead_note'));
    $success = $this->fieldText('success_message', $locale, __('web.home.lead_success'));
    $firstNameLabel = $this->fieldText('field_first_name', $locale, 'First Name');
    $lastNameLabel = $this->fieldText('field_last_name', $locale, 'Last Name');
    $emailLabel = $this->fieldText('field_email', $locale, 'Email');
    $firstNamePlaceholder = $this->fieldText('placeholder_first_name', $locale, 'Insert your first name');
    $lastNamePlaceholder = $this->fieldText('placeholder_last_name', $locale, 'Insert your last name');
    $emailPlaceholder = $this->fieldText('placeholder_email', $locale, 'Insert your email');
    $phonePlaceholder = $this->fieldText('placeholder_phone', $locale, __('web.home.lead_phone_placeholder'));
    $messagePlaceholder = $this->fieldText('placeholder_message', $locale, 'Type here...');
    $phoneHint = __('web.home.lead_phone_hint');
    $showCourseSelect = $this->showCourseSelect;
    $image = $this->fieldValue('image');
    $imageUrl = $image
        ? (str_starts_with($image, 'http')
            ? $image
            : \App\Support\S3Url::temporary($image))
        : asset('img/lead-from.jpg');
    $turnstileConfig = $this->turnstileWidgetConfig();
@endphp


@if (in_array($layout, ['complaint', 'contact'], true))
    {{-- ─── Contact / Complaint layout ────────────────────────────────────────── --}}
    {{-- x-data on <form>: gives the submit button access to phone validity.      --}}
    {{-- $wire.$watch re-syncs Alpine ph when Livewire resets the field.          --}}
    <section id="{{ $layout === 'contact' ? 'contact-us' : 'complaints' }}" class="full-bleed bg-[#eff3f3] py-6">
        <div class="app-container">
            <div class="grid gap-10 lg:grid-cols-[0.75fr_1.25fr] lg:items-start">
                <div class="min-h-[360px] rounded-3xl bg-cover bg-center shadow-card sm:min-h-[480px] lg:min-h-[560px]"
                    style="background-image: url('{{ $imageUrl }}');"></div>

                <div class="space-y-8">
                    <div>
                        <h2 class="text-3xl font-bold text-[#322f37] sm:text-4xl">{{ $title }}</h2>
                        <p class="mt-3 text-sm text-secondary sm:text-base">{{ $subtitle }}</p>
                    </div>

                    <form class="space-y-6"
                        x-data="{
                            ph: '',
                            turnstileVerified: false,
                            get ok() { return /^5\d{8}$/.test(this.ph.replace(/[٠-٩]/g, (c) => '٠١٢٣٤٥٦٧٨٩'.indexOf(c)).replace(/\D/g, '').replace(/^966/, '').replace(/^0+/, '')); },
                            get turnstileOk() { return {{ $turnstileConfig['enabled'] ? 'false' : 'true' }} || this.turnstileVerified; },
                            init() { $wire.$watch('phone', v => { this.ph = v ?? ''; }); }
                        }"
                        x-on:turnstile-verified.window="turnstileVerified = true"
                        x-on:turnstile-verified-reset.window="turnstileVerified = false"
                        x-on:turnstile-reset.window="turnstileVerified = false"
                        x-on:submit.prevent="(ph.trim() === '' || ok) && turnstileOk && $wire.submit()">

                        <input type="text" wire:model.defer="companyFax" autocomplete="off" tabindex="-1"
                            style="position:absolute;left:-9999px;top:-9999px;width:0;height:0;opacity:0;" aria-hidden="true">

                        @if ($submitted)
                            <div
                                class="rounded-full border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">
                                {{ $success }}
                            </div>
                        @endif

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-[#4f4b56]">{{ $firstNameLabel }}</label>
                                <input type="text" wire:model.defer="firstName"
                                    placeholder="{{ $firstNamePlaceholder }}"
                                    class="w-full rounded-full border border-[#d5d5d5] bg-white px-5 py-3 text-sm font-semibold text-[#4f4b56] placeholder:text-[#afafaf]">
                                @error('firstName')
                                    <div class="text-xs text-alert">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-[#4f4b56]">{{ $lastNameLabel }}</label>
                                <input type="text" wire:model.defer="lastName"
                                    placeholder="{{ $lastNamePlaceholder }}"
                                    class="w-full rounded-full border border-[#d5d5d5] bg-white px-5 py-3 text-sm font-semibold text-[#4f4b56] placeholder:text-[#afafaf]">
                                @error('lastName')
                                    <div class="text-xs text-alert">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-[#4f4b56]">{{ $emailLabel }}</label>
                                <input type="email" wire:model.defer="email" placeholder="{{ $emailPlaceholder }}"
                                    class="w-full rounded-full border border-[#d5d5d5] bg-white px-5 py-3 text-sm font-semibold text-[#4f4b56] placeholder:text-[#afafaf]">
                                @error('email')
                                    <div class="text-xs text-alert">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-[#4f4b56]">{{ $phoneLabel }}</label>
                                <div class="flex items-center gap-3 rounded-full border border-[#d5d5d5] bg-white px-5 py-3 rtl:flex-row-reverse"
                                    :class="{ '!border-green-500': ph.trim() !== '' && ok, '!border-red-400': ph.trim() !== '' && !ok }">
                                    <img src="/img/sa-flag.png" alt="SA"
                                        class="h-5 w-5 shrink-0 rounded-full object-cover">
                                    <span class="text-sm font-semibold text-[#afafaf]">+966</span>
                                    <span class="h-6 w-px bg-[#d6d6d6]"></span>
                                    <input type="tel" wire:model.defer="phone"
                                        x-on:input="ph = $event.target.value"
                                        placeholder="{{ $phonePlaceholder }}"
                                        class="ltr-input w-full border-0 bg-transparent text-sm font-semibold text-[#4f4b56] placeholder:text-[#afafaf] focus:outline-none">
                                    <svg x-show="ph.trim() !== '' && ok" style="display:none"
                                        class="h-4 w-4 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg x-show="ph.trim() !== '' && !ok" style="display:none"
                                        class="h-4 w-4 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                @error('phone')
                                    <div class="text-xs text-alert">{{ $message }}</div>
                                @enderror
                                <p class="text-xs text-[#afafaf]">{{ $phoneHint }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-[#242528]">{{ $messageLabel }}</label>
                            <textarea rows="4" wire:model.defer="message" placeholder="{{ $messagePlaceholder }}"
                                class="w-full rounded-xl border border-[#d5d5d5] bg-white px-5 py-4 text-sm font-semibold text-[#4f4b56] placeholder:text-[#afafaf]"></textarea>
                            @error('message')
                                <div class="text-xs text-alert">{{ $message }}</div>
                            @enderror
                        </div>

                        @include('livewire.partials.turnstile', ['turnstileConfig' => $turnstileConfig])

                        <button type="submit"
                            :disabled="(ph.trim() !== '' && !ok) || !turnstileOk"
                            :class="{ 'opacity-50 cursor-not-allowed': (ph.trim() !== '' && !ok) || !turnstileOk }"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70 cursor-wait"
                            wire:target="submit"
                            class="w-full rounded-full bg-brand-accent px-8 py-4 text-sm font-semibold text-white transition duration-150 hover:brightness-110 hover:-translate-y-0.5 active:translate-y-0">
                            <span wire:loading.remove wire:target="submit">{{ $submitLabel }}</span>
                            <span wire:loading wire:target="submit" class="inline-flex items-center justify-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ $submitLabel }}
                            </span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@else
    {{-- ─── Default layout ─────────────────────────────────────────────────────── --}}
    {{-- x-data on <form>: tracks which phone keys are invalid via $dispatch.      --}}
    {{-- Each phone wrapper dispatches 'phone-validity' when its value changes.    --}}
    {{-- $wire.$watch inside each wrapper re-syncs ph after Livewire resets.      --}}
    <section id="contact" class="py-6">
        <div class="app-container">
            <div class="grid gap-8 rounded-2xl p-6 shadow-card sm:p-10">
                <div class="space-y-4">
                    {{-- <p class="text-sm font-semibold text-brand-accent">{{ $tag }}</p> --}}
                    <h2 class="text-sm font-semibold text-neutral-text">{{ $title }}</h2>
                    {{-- <p class="text-sm text-secondary">{{ $subtitle }}</p> --}}
                    {{-- <adiv class="overflow-hidden rounded-2xl bg-white">
                        <img src="{{ $imageUrl }}" alt="{{ $title }}"
                            class="h-[100px] w-full object-cover">
                    </adiv> --}}
                </div>

                <form class="space-y-4 rounded-2xl bg-white p-6 shadow-card"
                    x-data="{
                        invalidPhones: [],
                        turnstileVerified: false,
                        updatePhoneValidity(key, valid) {
                            const i = this.invalidPhones.indexOf(key);
                            if (valid && i >= 0) this.invalidPhones.splice(i, 1);
                            else if (!valid && i < 0) this.invalidPhones.push(key);
                        },
                        get turnstileOk() { return {{ $turnstileConfig['enabled'] ? 'false' : 'true' }} || this.turnstileVerified; },
                        get allOk() { return this.invalidPhones.length === 0 && this.turnstileOk; }
                    }"
                    x-on:phone-validity.window="updatePhoneValidity($event.detail.key, $event.detail.valid)"
                    x-on:turnstile-verified.window="turnstileVerified = true"
                    x-on:turnstile-verified-reset.window="turnstileVerified = false"
                    x-on:turnstile-reset.window="turnstileVerified = false"
                    x-on:submit.prevent="allOk && $wire.submit()">

                    <input type="text" wire:model.defer="companyFax" autocomplete="off" tabindex="-1"
                        style="position:absolute;left:-9999px;top:-9999px;width:0;height:0;opacity:0;" aria-hidden="true">

                    @if ($submitted)
                        <div
                            class="rounded-full border border-success/40 bg-success/10 px-4 py-3 text-sm text-success">
                            {{ $success }}
                        </div>
                    @endif

                    @php
                        $configuredFields = $this->dynamicFields;
                    @endphp

                    @if (count($configuredFields))
                        @php
                            $visibleFieldCount = collect($configuredFields)
                                ->filter(function ($f) {
                                    $name = $f['name'] ?? '';
                                    $type = $f['type'] ?? 'text';
                                    return filled($name) &&
                                        !($type === 'select' && in_array($name, ['course', 'course_id', 'courseId']));
                                })
                                ->count();
                        @endphp
                        <div class="grid gap-3 {{ $visibleFieldCount > 1 ? 'sm:grid-cols-2' : '' }}">
                            @foreach ($configuredFields as $field)
                                @php
                                    $fieldName = $field['name'] ?? '';
                                    $fieldType = $field['type'] ?? 'text';
                                    $fieldLabel = $field["label_{$locale}"] ?? ($field['label_en'] ?? $fieldName);
                                    $isCourseField =
                                        in_array($fieldName, ['course', 'course_id', 'courseId'], true) &&
                                        $fieldType === 'select';
                                @endphp

                                @if (filled($fieldName))
                                    @if ($isCourseField)
                                        @continue
                                    @endif
                                    <div class="space-y-2 {{ $fieldType === 'textarea' ? 'sm:col-span-2' : '' }}">
                                        <label
                                            class="text-xs font-semibold text-secondary">{{ $fieldLabel }}</label>

                                        @if ($fieldType === 'textarea')
                                            <textarea rows="3" wire:model.defer="formData.{{ $fieldName }}"
                                                class="w-full rounded-2xl border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text"></textarea>

                                        @elseif ($fieldType === 'tel')
                                            <div class="flex items-center gap-2 rounded-full border border-border-neutral bg-white px-4 py-3 rtl:flex-row-reverse"
                                                x-data="{
                                                    ph: '',
                                                    get ok() { return /^5\d{8}$/.test(this.ph.replace(/[٠-٩]/g, (c) => '٠١٢٣٤٥٦٧٨٩'.indexOf(c)).replace(/\D/g, '').replace(/^966/, '').replace(/^0+/, '')); },
                                                    update(v) {
                                                        this.ph = v ?? '';
                                                        this.$dispatch('phone-validity', { key: '{{ $fieldName }}', valid: !this.ph || this.ok });
                                                    },
                                                    init() {
                                                        $wire.$watch('formData', data => this.update(data['{{ $fieldName }}'] ?? ''));
                                                    }
                                                }"
                                                :class="{ '!border-green-500': ph.trim() !== '' && ok, '!border-red-400': ph.trim() !== '' && !ok }">
                                                <img src="/img/sa-flag.png" alt="SA"
                                                    class="h-5 w-5 shrink-0 rounded-full object-cover">
                                                <span class="text-sm font-semibold text-[#afafaf]">+966</span>
                                                <span class="h-5 w-px bg-[#d6d6d6]"></span>
                                                <input type="tel"
                                                    wire:model.defer="formData.{{ $fieldName }}"
                                                    x-on:input="update($event.target.value)"
                                                    placeholder="{{ $phonePlaceholder }}"
                                                    class="ltr-input w-full border-0 bg-transparent text-sm text-neutral-text focus:outline-none">
                                                <svg x-show="ph.trim() !== '' && ok" style="display:none"
                                                    class="h-4 w-4 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                                <svg x-show="ph.trim() !== '' && !ok" style="display:none"
                                                    class="h-4 w-4 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>

                                        @else
                                            <input type="{{ $fieldType === 'email' ? 'email' : 'text' }}"
                                                wire:model.defer="formData.{{ $fieldName }}"
                                                class="w-full rounded-full border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text">
                                        @endif

                                        @error('formData.' . $fieldName)
                                            <div class="text-xs text-alert">{{ $message }}</div>
                                        @enderror
                                        @if ($fieldType === 'tel')
                                            <p class="text-xs text-[#afafaf]">{{ $phoneHint }}</p>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        @if ($showCourseSelect || isset($lockedBundle))
                            <div class="space-y-2">
                                <label class="text-xs font-semibold text-secondary">{{ $courseLabel }}</label>
                                <select wire:model.defer="courseId"
                                    {{-- @disabled(isset($lockedBundle) || $this->lockedCourseId) --}}
                                    class="w-full rounded-full border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text">
                                    @if (isset($lockedBundle))
                                        <option value="" selected>{{ $lockedBundle->getTranslation('title', app()->getLocale()) }} - {{ number_format($lockedBundle->final_price ?? $lockedBundle->price) }} {{ __('web.common.currency.sar') }}</option>
                                    @else
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}" @selected($loop->first)>
                                                {{ $course->getTranslation('title', app()->getLocale()) }} - {{ number_format($course->final_price ?? $course->price) }} {{ __('web.common.currency.sar') }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('courseId')
                                    <div class="text-xs text-alert">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif
                    @else
                        <div class="grid gap-3">
                            {{-- <div class="space-y-2">
                                <label class="text-xs font-semibold text-secondary">{{ $nameLabel }}</label>
                                <input type="text" wire:model.defer="name"
                                    class="w-full rounded-full border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text">
                                @error('name')
                                    <div class="text-xs text-alert">{{ $message }}</div>
                                @enderror
                            </div> --}}
                            <div class="space-y-2">
                                <label class="text-xs font-semibold text-secondary">{{ $phoneLabel }}</label>
                                <div class="flex items-center gap-2 rounded-full border border-border-neutral bg-white px-4 py-3 rtl:flex-row-reverse"
                                    x-data="{
                                        ph: '',
                                        get ok() { return /^5\d{8}$/.test(this.ph.replace(/[٠-٩]/g, (c) => '٠١٢٣٤٥٦٧٨٩'.indexOf(c)).replace(/\D/g, '').replace(/^966/, '').replace(/^0+/, '')); },
                                        update(v) {
                                            this.ph = v ?? '';
                                            this.$dispatch('phone-validity', { key: 'phone', valid: !this.ph || this.ok });
                                        },
                                        init() { $wire.$watch('phone', v => this.update(v)); }
                                    }"
                                    :class="{ '!border-green-500': ph.trim() !== '' && ok, '!border-red-400': ph.trim() !== '' && !ok }">
                                    <img src="/img/sa-flag.png" alt="SA"
                                        class="h-5 w-5 shrink-0 rounded-full object-cover">
                                    <span class="text-sm font-semibold text-[#afafaf]">+966</span>
                                    <span class="h-5 w-px bg-[#d6d6d6]"></span>
                                    <input type="tel" wire:model.defer="phone"
                                        x-on:input="update($event.target.value)"
                                        placeholder="{{ $phonePlaceholder }}"
                                        class="ltr-input w-full border-0 bg-transparent text-sm text-neutral-text focus:outline-none">
                                    <svg x-show="ph.trim() !== '' && ok" style="display:none"
                                        class="h-4 w-4 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <svg x-show="ph.trim() !== '' && !ok" style="display:none"
                                        class="h-4 w-4 shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                @error('phone')
                                    <div class="text-xs text-alert">{{ $message }}</div>
                                @enderror
                                <p class="text-xs text-[#afafaf]">{{ $phoneHint }}</p>
                            </div>
                        </div>

                        @if ($showCourseSelect || isset($lockedBundle))
                            <div class="space-y-2">
                                <label class="text-xs font-semibold text-secondary">{{ $courseLabel }}</label>
                                <select wire:model.defer="courseId"
                                    {{-- @disabled(isset($lockedBundle) || $this->lockedCourseId) --}}
                                    class="w-full rounded-full border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text">
                                    @if (isset($lockedBundle))
                                        <option value="" selected>{{ $lockedBundle->getTranslation('title', app()->getLocale()) }} - {{ number_format($lockedBundle->final_price ?? $lockedBundle->price) }} {{ __('web.common.currency.sar') }}</option>
                                    @else
                                        @foreach ($courses as $course)
                                            <option value="{{ $course->id }}" @selected($loop->first)>
                                                {{ $course->getTranslation('title', app()->getLocale()) }} - {{ number_format($course->final_price ?? $course->price) }} {{ __('web.common.currency.sar') }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('courseId')
                                    <div class="text-xs text-alert">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        {{-- <div class="space-y-2">
                            <label class="text-xs font-semibold text-secondary">{{ $messageLabel }}</label>
                            <textarea rows="3" wire:model.defer="message"
                                class="w-full rounded-2xl border border-border-neutral bg-white px-4 py-3 text-sm text-neutral-text"></textarea>
                            @error('message')
                                <div class="text-xs text-alert">{{ $message }}</div>
                            @enderror
                        </div> --}}
                    @endif

                    @include('livewire.partials.turnstile', ['turnstileConfig' => $turnstileConfig])

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                            :disabled="!allOk"
                            :class="{ 'opacity-50 cursor-not-allowed': !allOk }"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-70 cursor-wait"
                            wire:target="submit"
                            class="rounded-full bg-brand-accent px-8 py-3 text-sm font-semibold text-white transition duration-150 hover:brightness-110 hover:-translate-y-0.5 active:translate-y-0">
                            <span wire:loading.remove wire:target="submit">{{ $submitLabel }}</span>
                            <span wire:loading wire:target="submit" class="inline-flex items-center justify-center gap-2">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                {{ $submitLabel }}
                            </span>
                        </button>
                        <span class="text-xs text-muted">{{ $note }}</span>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endif
