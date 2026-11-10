<?php

namespace App\Http\Controllers;

use App\DTOs\PageDto;
use App\Enums\AttachmentVisibility;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\Page;
use App\Repositories\Contracts\PageRepositoryInterface;
use App\Support\S3Url;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController
{
    public function __construct(private readonly PageRepositoryInterface $pages) {}

    public function show(?string $slug = null): View
    {
        // Malformed URLs (e.g. scanner probes like "//blog/wp-includes/...") can reach here without a slug.
        abort_if(blank($slug), 404);

        $page = $this->pages->findPublishedBySlug(rtrim($slug, '/'));
        abort_unless($page, 404);

        return $this->render($page);
    }

    /**
     * Admin-only view of any page, including landing versions moved to draft.
     */
    public function preview(Page $page): View
    {
        return $this->render($page);
    }

    private function render(Page $page): View
    {
        $relations = [
            'sections' => fn ($query) => $query
                ->active()
                ->orderBy('sort_order')
                ->with('sectionFields'),
        ];

        if (in_array($page->template, Page::LANDABLE_TEMPLATES, true)) {
            $relations[] = 'landable';
        }

        $page->load($relations);

        $dto = new PageDto(
            $page->getTranslation('title', app()->getLocale()),
            $page->slug,
            $page->getTranslation('body', app()->getLocale()),
            $page->template
        );

        $view = $page->template ? "pages.{$page->template}" : 'pages.show';
        if (! view()->exists($view)) {
            $view = 'pages.show';
        }

        [$metaTitle, $metaDescription, $ogImageUrl] = $this->resolveSeoMeta($page);

        $data = [
            'page' => $dto,
            'sections' => $page->sections,
            'metaTitle' => $metaTitle,
            'metaDescription' => $metaDescription,
            'ogImageUrl' => $ogImageUrl,
        ];

        if (in_array($page->template, Page::LANDABLE_TEMPLATES, true)) {
            $data['landable'] = $page->landable;
        }

        if ($page->template === 'course-profile') {
            $data['ctaLabel'] = $page->ctaLabel(app()->getLocale());
            $data['ctaUrl'] = $page->ctaUrl();
            $data['courseGroups'] = $page->landable instanceof Course
                ? $page->landable->groups()->active()->with('instructors')->orderBy('start_at')->get()
                : null;
            $data['courseAttachments'] = $page->landable instanceof Course
                ? $page->landable->attachments()->where('visibility', AttachmentVisibility::Profile)->get()
                : null;
            $data['bundleCourses'] = $page->landable instanceof Bundle
                ? $page->landable->courses()
                    ->with([
                        'groups' => fn ($query) => $query->active()->with('instructors')->orderBy('start_at'),
                        'attachments' => fn ($query) => $query->where('visibility', AttachmentVisibility::Profile),
                    ])
                    ->get()
                : null;
        }

        return view($view, $data);
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function resolveSeoMeta(Page $page): array
    {
        $locale = app()->getLocale();

        $metaTitle = trim((string) $page->getTranslation('meta_title', $locale)) ?: null;
        $metaDescription = trim(strip_tags((string) $page->getTranslation('meta_description', $locale))) ?: null;
        $ogImageUrl = $page->meta_image ? S3Url::temporary($page->meta_image) : null;

        $landable = in_array($page->template, Page::LANDABLE_TEMPLATES, true) ? $page->landable : null;

        if ($landable) {
            $metaTitle ??= trim((string) $landable->getTranslation('title', $locale)) ?: null;

            $summary = trim(strip_tags((string) $landable->getTranslation('summary', $locale)));
            $metaDescription ??= $summary !== '' ? Str::limit($summary, 160) : null;

            $ogImageUrl ??= $landable->image_url ?? null;
        }

        $metaTitle ??= trim((string) $page->getTranslation('title', $locale)) ?: null;

        if ($metaDescription === null) {
            $body = trim(strip_tags((string) $page->getTranslation('body', $locale)));
            $metaDescription = $body !== '' ? Str::limit($body, 160) : null;
        }

        return [$metaTitle, $metaDescription, $ogImageUrl];
    }
}
