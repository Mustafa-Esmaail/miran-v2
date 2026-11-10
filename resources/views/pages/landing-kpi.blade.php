{{-- KPI online landing page (kpi-landing/index.html, 2026-10-31). --}}
<x-layouts.app :meta-title="$metaTitle ?? null" :meta-description="$metaDescription ?? null" :og-image-url="$ogImageUrl ?? null"
    :hide-chrome="true">
    @if (app(\Illuminate\Foundation\Vite::class)->isRunningHot())
        @vite(['resources/css/landing-v2.css', 'resources/css/landing-course.css'])
    @else
        <style>{!! app(\Illuminate\Foundation\Vite::class)->content('resources/css/landing-v2.css') !!}</style>
        <style>{!! app(\Illuminate\Foundation\Vite::class)->content('resources/css/landing-course.css') !!}</style>
    @endif

    <div class="lv2 lv-course lv-kpi" dir="rtl">
        <div class="phone">
            @foreach ($sections as $section)
                @includeIf("sections.{$section->type}", ['section' => $section, 'landable' => $landable ?? null])
            @endforeach

            <footer class="foot">
                <div class="wrap">
                    {{ __('web.landing_v2.footer_line') }}<br>
                    <a href="{{ url('/privacy-policy') }}" target="_blank" rel="noopener">{{ __('web.landing_course.privacy_policy') }}</a>
                    · {{ __('web.landing_v2.footer_policies') }}
                </div>
            </footer>
        </div>
    </div>
</x-layouts.app>
