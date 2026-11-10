<?php

namespace App\Livewire\Blog;

use App\DTOs\BlogListItemDto;
use App\Enums\BlogStatus;
use App\Models\Blog;
use App\Repositories\Contracts\BlogCategoryRepositoryInterface;
use App\Services\BlogService;
use App\Support\S3Url;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class BlogList extends Component
{
    use WithPagination;

    protected $queryString = [
        'search' => ['except' => ''],
        'categoryId' => ['except' => null],
    ];

    public string $search = '';

    public ?int $categoryId = null;

    public int $perPage = 9;

    public function updated($field): void
    {
        if (in_array($field, ['search', 'categoryId'], true)) {
            $this->resetPage();
        }
    }

    public function render(BlogService $blogService, BlogCategoryRepositoryInterface $categories): View
    {
        $locale = app()->getLocale();
        $query = $blogService->queryForViewer(auth()->user())
            ->with(['author', 'category'])
            ->withCount(['comments as approved_comments_count' => function (Builder $builder) {
                $builder->approved();
            }])
            ->latest();

        $search = trim($this->search);
        if ($search !== '') {
            $query->where("title->{$locale}", 'like', "%{$search}%");
        }

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }

        $paginator = null;
        $items = collect();
        $categorySections = collect();
        $activeCategories = $categories->getActiveOrderedByName($locale);

        if ($search !== '' || $this->categoryId) {
            $paginator = $query->paginate($this->perPage);
            $items = $paginator->getCollection()->map(
                fn (Blog $blog): BlogListItemDto => $this->mapBlogToDto($blog, $locale)
            );
        } else {
            $categorySections = $activeCategories->map(function ($category) use ($blogService, $locale): array {
                $categoryQuery = $blogService->queryForViewer(auth()->user())
                    ->with(['author', 'category'])
                    ->withCount(['comments as approved_comments_count' => function (Builder $builder) {
                        $builder->approved();
                    }])
                    ->where('category_id', $category->id)
                    ->latest()
                    ->limit(4);

                $sectionItems = $categoryQuery->get()
                    ->map(fn (Blog $blog): BlogListItemDto => $this->mapBlogToDto($blog, $locale));

                return [
                    'category' => $category,
                    'items' => $sectionItems,
                ];
            })->filter(fn (array $section): bool => $section['items']->isNotEmpty())->values();
        }

        return view('livewire.blog.blog-list', [
            'paginator' => $paginator,
            'items' => $items,
            'categories' => $activeCategories,
            'categorySections' => $categorySections,
        ])->layout('layouts.app', ['fullWidth' => true]);
    }

    protected function mapBlogToDto(Blog $blog, string $locale): BlogListItemDto
    {
        $title = $blog->getTranslation('title', $locale);
        $shortDescription = $blog->getTranslation('short_description', $locale);
        $excerptSource = $shortDescription;
        $imagePath = $blog->image_path;
        $imageUrl = $imagePath ? S3Url::temporary($imagePath) : null;

        return new BlogListItemDto(
            id: $blog->id,
            title: $title,
            slug: $blog->slug,
            excerpt: trim(strip_tags((string) $excerptSource)),
            categoryName: $blog->category?->getTranslation('title', $locale) ?? __('web.blog.uncategorized'),
            authorName: $blog->author?->name ?? __('web.blog.unknown_author'),
            publishedAt: $blog->published_at?->format('M d, Y'),
            isDraft: $blog->status === BlogStatus::Draft,
            commentsCount: $blog->approved_comments_count ?? 0,
            imageUrl: $imageUrl,
        );
    }
}
