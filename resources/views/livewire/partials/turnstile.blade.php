@php
    $turnstileConfig ??= $this->turnstileWidgetConfig();
@endphp

@if ($turnstileConfig['enabled'])
    @once
        <script>
            window.turnstileApiReady = false;
            window.turnstileOnLoad = function () {
                window.turnstileApiReady = true;
                window.dispatchEvent(new CustomEvent('turnstile-api-ready'));
            };
        </script>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=turnstileOnLoad&render=explicit" defer></script>
    @endonce

    <div
        wire:ignore
        x-data="{
            widgetId: null,
            render() {
                this.widgetId = window.turnstile.render(this.$refs.turnstileWidget, {
                    sitekey: @js($turnstileConfig['site_key']),
                    language: @js(app()->getLocale()),
                    callback: (token) => {
                        $wire.set('turnstileToken', token);
                        window.dispatchEvent(new CustomEvent('turnstile-verified'));
                    },
                    'expired-callback': () => {
                        $wire.set('turnstileToken', '');
                        window.dispatchEvent(new CustomEvent('turnstile-verified-reset'));
                    },
                    'error-callback': () => {
                        $wire.set('turnstileToken', '');
                        window.dispatchEvent(new CustomEvent('turnstile-verified-reset'));
                    },
                });
            },
            init() {
                if (window.turnstileApiReady) {
                    this.render();
                } else {
                    window.addEventListener('turnstile-api-ready', () => this.render(), { once: true });
                }
            }
        }"
        x-init="init()"
        x-on:turnstile-reset.window="widgetId !== null && window.turnstile && window.turnstile.reset(widgetId)">
        <div x-ref="turnstileWidget"></div>
    </div>

    @error('turnstileToken')
        <div class="text-xs text-alert">{{ $message }}</div>
    @enderror
@endif
