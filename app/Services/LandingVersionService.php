<?php

namespace App\Services;

use App\Exceptions\LandingVersionException;
use App\Models\Page;
use Illuminate\Support\Facades\DB;

/**
 * Swaps the live version of a landing line: the live page moves to
 * "{landing_key}-v{n}" as a draft, the replacement takes the landing_key slug
 * (the URL ads point at) as version n+1.
 */
class LandingVersionService
{
    /**
     * @throws LandingVersionException
     */
    public function publishNewVersion(Page $live, Page $replacement, ?string $versionName = null, ?string $marketingSource = null): Page
    {
        $this->ensureCanReplace($live, $replacement);

        $landingKey = $live->landing_key;
        $draftSlug = $this->draftSlug($live);

        if (Page::query()->where('slug', $draftSlug)->exists()) {
            throw new LandingVersionException(__('admin.landing_versions.errors.draft_slug_taken', ['slug' => $draftSlug]));
        }

        return DB::transaction(function () use ($live, $replacement, $landingKey, $draftSlug, $versionName, $marketingSource): Page {
            $nextVersion = (int) Page::query()->where('landing_key', $landingKey)->max('version') + 1;

            // Free the landing slug first: pages.slug is unique.
            $live->update([
                'slug' => $draftSlug,
                'is_active' => false,
                'is_published' => false,
                'drafted_at' => now(),
            ]);

            $replacement->update([
                'slug' => $landingKey,
                'landing_key' => $landingKey,
                'version' => $nextVersion,
                'version_name' => filled($versionName) ? $versionName : null,
                'marketing_source' => filled($marketingSource) ? $marketingSource : null,
                'is_active' => true,
                'is_published' => true,
                'published_at' => now(),
                'drafted_at' => null,
            ]);

            return $replacement->refresh();
        });
    }

    /**
     * The slug the live page moves to: its own version number (pmpon → pmpon-v1).
     */
    public function draftSlug(Page $live): string
    {
        return $live->landing_key.'-v'.($live->version ?? 1);
    }

    /**
     * Pages that can replace the live version: standalone landing pages (v1 of
     * their own line, never drafted) other than the live page itself.
     *
     * @return array<int, string>
     */
    public function replacementOptions(Page $live, string $locale): array
    {
        $candidates = Page::query()
            ->whereIn('template', Page::VERSIONED_TEMPLATES)
            ->whereKeyNot($live->getKey())
            ->whereNull('drafted_at')
            ->whereColumn('landing_key', 'slug')
            ->where('version', 1)
            ->orderByDesc('created_at')
            ->get();

        $lineSizes = Page::query()
            ->whereIn('landing_key', $candidates->pluck('landing_key'))
            ->selectRaw('landing_key, count(*) as aggregate')
            ->groupBy('landing_key')
            ->pluck('aggregate', 'landing_key');

        return $candidates
            ->filter(fn (Page $page): bool => (int) ($lineSizes[$page->landing_key] ?? 0) === 1)
            ->mapWithKeys(fn (Page $page): array => [$page->id => $page->getTranslation('title', $locale).' ('.$page->slug.')'])
            ->all();
    }

    /**
     * @throws LandingVersionException
     */
    private function ensureCanReplace(Page $live, Page $replacement): void
    {
        if (! $live->isLiveVersion()) {
            throw new LandingVersionException(__('admin.landing_versions.errors.not_live'));
        }

        if ($replacement->is($live)
            || ! $replacement->isVersioned()
            || $replacement->drafted_at !== null
            || $replacement->landing_key !== $replacement->slug
            || ! $this->isStandalone($replacement)) {
            throw new LandingVersionException(__('admin.landing_versions.errors.invalid_replacement'));
        }
    }

    /**
     * A page that is the only member of its landing line (not already part of another line's history).
     */
    private function isStandalone(Page $page): bool
    {
        return ! Page::query()
            ->where('landing_key', $page->landing_key)
            ->whereKeyNot($page->getKey())
            ->exists();
    }
}
