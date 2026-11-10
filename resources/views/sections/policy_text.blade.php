@php
    use App\Support\S3Url;

    $locale = app()->getLocale();
    $getText = fn(string $key, string $fallback = '') => $section->fieldText($key, $locale, $fallback);
    $body = $getText('body', '');
    $resolveAssetUrl = function (string $path): ?string {
        $path = trim($path);
        if ($path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//') || str_starts_with($path, 'data:') || str_starts_with($path, '#')) {
            return null;
        }

        return S3Url::temporary($path);
    };

    $body = preg_replace_callback(
        '/<img\b([^>]*)\bdata-id=["\']([^"\']+)["\']([^>]*)>/i',
        function (array $matches) use ($resolveAssetUrl): string {
            $resolved = $resolveAssetUrl($matches[2]);
            if (! $resolved) {
                return '<img'.$matches[1].'data-id="'.$matches[2].'"'.$matches[3].'>';
            }

            if (preg_match('/\bsrc=["\'][^"\']+["\']/i', $matches[1].$matches[3])) {
                return '<img'.$matches[1].'data-id="'.$matches[2].'"'.$matches[3].'>';
            }

            return '<img'.$matches[1].'data-id="'.$matches[2].'" src="'.$resolved.'"'.$matches[3].'>';
        },
        $body
    ) ?? $body;

    $body = preg_replace_callback(
        '/(<img\b[^>]*\bsrc=["\'])([^"\']+)(["\'][^>]*>)/i',
        function (array $matches) use ($resolveAssetUrl): string {
            $resolved = $resolveAssetUrl($matches[2]);

            return $matches[1].($resolved ?: $matches[2]).$matches[3];
        },
        $body
    ) ?? $body;

    $body = preg_replace_callback(
        '/(<a\b[^>]*\bhref=["\'])([^"\']+)(["\'][^>]*>)/i',
        function (array $matches) use ($resolveAssetUrl): string {
            $resolved = $resolveAssetUrl($matches[2]);

            return $matches[1].($resolved ?: $matches[2]).$matches[3];
        },
        $body
    ) ?? $body;
@endphp

<section class="section-shell">
    <div class="section-title">
        <div class="relative flex flex-col items-center gap-4 text-center py-5">
            <p class="text-[26px] font-bold text-primary sm:text-[32px]">
                {{ $getText('title', $section->title ?? __('web.home.categories_title')) }}
            </p>
            <p class="max-w-4xl text-base text-secondary">
                {{ $getText('subtitle', $section->subtitle ?? '') }}
            </p>
        </div>

    </div>
    @if ($body !== '')
        <div class="rich-content mx-auto w-full rounded-3xl bg-surface px-6 py-6 sm:px-10 lg:px-[72px]">
            {!! $body !!}
        </div>
    @endif
</section>
