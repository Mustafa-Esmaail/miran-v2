@php
    $turnstileConfig = $this->turnstileWidgetConfig();
@endphp

<div x-data="{
    ph: '',
    turnstileVerified: false,
    get ok() { return /^5\d{8}$/.test(this.ph.replace(/[٠-٩]/g, (c) => '٠١٢٣٤٥٦٧٨٩'.indexOf(c)).replace(/\D/g, '').replace(/^966/, '').replace(/^0+/, '')); },
    get turnstileOk() { return {{ $turnstileConfig['enabled'] ? 'false' : 'true' }} || this.turnstileVerified; },
    init() { $wire.$watch('phone', v => { this.ph = v ?? ''; }); },
    submit() {
        if ((this.ph.trim() === '' || this.ok) && this.turnstileOk) $wire.submit();
    },
}" x-on:turnstile-verified.window="turnstileVerified = true"
    x-on:turnstile-verified-reset.window="turnstileVerified = false"
    x-on:turnstile-reset.window="turnstileVerified = false">

    <form novalidate x-on:submit.prevent="submit()">
        <input type="text" wire:model="companyFax" autocomplete="off" tabindex="-1"
            style="position:absolute;left:-9999px;top:-9999px;width:0;height:0;opacity:0;" aria-hidden="true">

        <label class="lbl" for="lv2-phone">{{ __('web.landing_v2.phone_label') }}</label>
        <div class="phonefield"
            :class="{ 'valid': ph.trim() !== '' && ok, 'invalid': (ph.trim() !== '' && !ok) ||
                    @js($errors->has('phone')) }">
            <img class="flag" src="/img/sa-flag.png" alt="SA" width="20" height="20">
            <span>{{ \App\Livewire\Landing\LeadFormV2::COUNTRY_CODE }}</span>
            <input id="lv2-phone" type="tel" x-ref="phone" wire:model="phone" x-on:input="ph = $event.target.value"
                autocomplete="tel-national" placeholder="51 234 5678"
                aria-describedby="lv2-phone-error">
            <svg x-show="ph.trim() !== '' && ok" style="display:none" class="state ok" viewBox="0 0 20 20"
                fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
            </svg>
            <svg x-show="ph.trim() !== '' && !ok" style="display:none" class="state bad" viewBox="0 0 20 20"
                fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd" />
            </svg>
        </div>

        @error('phone')
            <div class="err" id="lv2-phone-error">{{ $message }}</div>
        @else
            <p class="hint" id="lv2-phone-error">{{ __('web.home.lead_phone_hint') }}</p>
        @enderror

        <div class="turnstile">
            @include('livewire.partials.turnstile', ['turnstileConfig' => $turnstileConfig])
        </div>

        <button class="btn" type="submit" :disabled="(ph.trim() !== '' && !ok) || !turnstileOk" wire:loading.attr="disabled"
            wire:target="submit">
            <span wire:loading.remove wire:target="submit">{{ $submitText }}</span>
            <span wire:loading wire:target="submit">{{ __('web.landing_v2.sending') }}</span>
        </button>
    </form>
</div>
