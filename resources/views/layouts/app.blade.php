<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $metaTitle }}</title>
    @if ($metaDescription)
        <meta name="description" content="{!! $metaDescription !!}">
    @endif
    <meta property="og:title" content="{{ $title ?? $metaTitle }}">
    @if ($metaDescription)
        <meta property="og:description" content="{!! $metaDescription !!}">
    @endif
    <meta property="og:image" content="{{ $ogImageUrl }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="preload" href="{{ asset('fonts/cairo/cairo-arabic.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/cairo/cairo-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @php $gtmId = app(\App\Services\Settings\SettingsService::class)->getValue('gtm.container_id', 'GTM-55QMTJQ'); @endphp
    <!-- Google Tag Manager -->
    <script>
        (function(w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', @js($gtmId));

        window.trackEvent = function(name, data) {
            data = data || {};
            if (Array.isArray(data.items)) {
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({ event: name, ecommerce: data });

                return;
            }
            window.dataLayer.push(Object.assign({ event: name }, data));
        };
    </script>
    <!-- End Google Tag Manager -->
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-11381535946"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'AW-11381535946');
    </script>
    <!-- End Google tag (gtag.js) -->
    <!-- Snap Pixel Code -->
    {{-- <script type='text/javascript'>
        (function(e, t, n) {
            if (e.snaptr) return;
            var a = e.snaptr = function() {
                a.handleRequest ? a.handleRequest.apply(a, arguments) : a.queue.push(arguments)
            };
            a.queue = [];
            var s = 'script';
            r = t.createElement(s);
            r.async = !0;
            r.src = n;
            var u = t.getElementsByTagName(s)[0];
            u.parentNode.insertBefore(r, u);
        })(window, document,
            'https://sc-static.net/scevent.min.js');

        snaptr('init', '9fd65226-4fdc-458b-bdae-d8944864f8fb', {
            'user_email': '{{ auth('web')->check() ? auth('web')->user()->email : '' }}'
        });

        snaptr('track', 'PAGE_VIEW');
    </script> --}}
    <!-- End Snap Pixel Code -->
</head>

<body class="min-h-screen bg-page text-primary">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0"
            width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    @if (session('gtm_login'))
        <x-gtm-event event="login" :data="['method' => 'web']" />
    @endif
    @foreach (\App\Support\Gtm\GtmEventQueue::pull() as $gtmQueuedEvent)
        <x-gtm-event :event="$gtmQueuedEvent['event']" :data="$gtmQueuedEvent['data']" />
    @endforeach

    @impersonating('web')
        <div style="position:sticky;top:0;z-index:9999;background:#111827;color:#fff;padding:10px 16px;font-size:14px;text-align:center;">
            {{ __('web.impersonation.banner', ['name' => auth('web')->user()?->name]) }}
            <a href="{{ route('impersonate.leave') }}" style="color:#fbbf24;text-decoration:underline;margin-inline-start:8px;">
                {{ __('web.impersonation.leave') }}
            </a>
        </div>
    @endImpersonating
    {{-- Page loader is disabled for now. Re-enable when needed. --}}

    @unless ($hideHeader ?? ($hideChrome ?? false))
        <x-layout.site-header :logo-url="$logoUrl" :brand="$siteTitle" :nav-links="$navLinks" :cart-count="auth('web')->check() ? auth('web')->user()->cart?->items?->count() ?? 0 : 0" :notification-count="$notificationCount ?? 0"
            :topbar-left="$topbarLeft" :topbar-center="$topbarCenter" :topbar-right="$topbarRight" />
    @endunless

    <div id="toast-root" class="toast-root" aria-live="polite" aria-atomic="true">
        <div id="toast-message" class="toast-message" role="status"></div>
    </div>

    @if (session('status'))
        <script>
            window.__initialToast = @json(session('status'));
        </script>
    @endif

    {{-- Landing pages (hide-chrome) render without the fade-in so the hero counts as visible content for LCP. --}}
    <main class="{{ $hideChrome ?? false ? 'py-0' : 'page-enter ' . ($fullWidth ?? false ? 'py-0' : 'app-container') }}">
        {{ $slot }}
    </main>

    @unless ($hideFooter ?? ($hideChrome ?? false))
        <x-site.footer :footer-address-label="$footerAddressLabel" :footer-address-value="$footerAddressValue" :footer-hours="$footerHours" :footer-contact-label="$footerContactLabel" :footer-email="$footerEmail"
            :footer-whatsapp="$footerWhatsapp" :footer-policies-label="$footerPoliciesLabel" :footer-policy-links="$footerPolicyLinks" :payments-strip-url="$paymentsStripUrl" :payment-method-logos="$paymentMethodLogos"
            :footer-certification-cards="$footerCertificationCards" :social-links="$socialLinks" />
    @endunless

    @unless ($hideChrome ?? false)
        <x-floating-contact :phone="$contactPhone" :whatsapp="$whatsappPhone" :message="$whatsappMessage" :raised="$floatingContactRaised ?? false" />
    @endunless

    @livewireScripts
</body>

</html>
