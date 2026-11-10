<?php

namespace App\View\Components\Site;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

class Header extends Component
{
    public array $navLinks;

    public int $cartCount;

    public bool $isRtl;

    public ?string $logoUrl;

    public array $courseCategories;

    public function __construct(
        CategoryRepositoryInterface $categories,
        array $navLinks = [],
        int $cartCount = 0,
        ?string $logoUrl = null
    ) {
        $this->cartCount = $cartCount;
        $this->isRtl = app()->getLocale() === 'ar';
        $this->logoUrl = $logoUrl ?? 'https://themiran.net/wp-content/uploads/2025/02/contact-mail_3095583-1.png';
        $coursesLabel = __('web.nav.courses');
        $locale = app()->getLocale();

        $this->navLinks = collect($navLinks)->map(function (array $link) use ($coursesLabel) {
            $label = (string) ($link['label'] ?? '');
            $href = $link['href'] ?? $link['url'] ?? '#';
            $isCourses = $label !== ''
                && (Str::contains(Str::lower($label), 'course') || $label === $coursesLabel);

            return [
                'label' => $label,
                'href' => $href,

                'active' => (bool) ($link['active'] ?? false),
                'new_tab' => (bool) ($link['new_tab'] ?? false),
                'is_courses' => $isCourses,
                'children' => $link['children'] ?? [],
            ];
        })->all();

        $this->courseCategories = $categories->getHomeCategories(4)
            ->map(function ($category) use ($locale) {
                $name = $category->getTranslation('name', $locale)
                    ?? $category->getTranslation('name', 'en')
                    ?? $category->getTranslation('name', 'ar')
                    ?? '';

                return [
                    'id' => $category->id,
                    'name' => $name,
                    'href' => route('courses.index', ['categoryId' => $category->id]),
                ];
            })
            ->filter(fn (array $category) => $category['name'] !== '')
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('components.site.header');
    }
}
