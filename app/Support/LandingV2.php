<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * View helpers shared by the landing v2 sections (resources/views/sections/landing_v2_*).
 */
class LandingV2
{
    /**
     * Resolves an image stored on a section field: absolute URLs are kept,
     * "/img/..." paths are public assets, anything else is an S3 key.
     */
    public static function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return match (true) {
            str_starts_with($path, 'http') => $path,
            str_starts_with($path, '/') => asset(ltrim($path, '/')),
            default => S3Url::temporary($path),
        };
    }

    /**
     * Escapes the text and turns *marked* words into the brand-coloured <em>.
     */
    public static function highlight(?string $text): HtmlString
    {
        $escaped = e((string) $text);

        return new HtmlString(preg_replace('/\*(.+?)\*/u', '<em>$1</em>', $escaped) ?? $escaped);
    }

    /**
     * Picks the current-locale value of a repeater item key ("label" → label_ar / label_en).
     */
    public static function itemText(array $item, string $key, string $locale): string
    {
        foreach ([$locale, 'ar', 'en'] as $candidate) {
            $value = trim((string) ($item["{$key}_{$candidate}"] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
