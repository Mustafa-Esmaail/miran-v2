<?php

namespace App\Http\Controllers;

use App\Models\Bundle;
use App\Models\Course;
use App\Models\Page;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const CACHE_KEY = 'sitemap.xml';

    private const CACHE_TTL_SECONDS = 3600;

    public function __invoke(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, fn (): string => view('sitemap', [
            'urls' => $this->urls(),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Public website URLs only: active pages (excluding course-profile pages), published courses and active bundles.
     *
     * @return Collection<int, array{loc: string, lastmod: ?string}>
     */
    private function urls(): Collection
    {
        $static = collect([
            route('home'),
            route('courses.index'),
            route('bundles.index'),
        ])->map(fn (string $loc): array => ['loc' => $loc, 'lastmod' => null]);

        $pages = Page::query()
            ->active()
            ->where('slug', '!=', 'home')
            ->where(fn ($query) => $query->whereNull('template')->orWhere('template', '!=', 'course-profile'))
            ->get(['slug', 'updated_at'])
            ->map(fn (Page $page): array => [
                'loc' => route('pages.show', $page->slug),
                'lastmod' => $page->updated_at?->toAtomString(),
            ]);

        $courses = Course::query()
            ->published()
            ->get(['slug', 'updated_at'])
            ->map(fn (Course $course): array => [
                'loc' => route('courses.show', $course->slug),
                'lastmod' => $course->updated_at?->toAtomString(),
            ]);

        $bundles = Bundle::query()
            ->where('is_active', true)
            ->get(['slug', 'updated_at'])
            ->map(fn (Bundle $bundle): array => [
                'loc' => route('bundles.select-groups', ['bundle' => $bundle->slug]),
                'lastmod' => $bundle->updated_at?->toAtomString(),
            ]);

        return $static->concat($pages)->concat($courses)->concat($bundles)->values();
    }
}
