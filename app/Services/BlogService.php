<?php

namespace App\Services;

use App\Enums\BlogStatus;
use App\Models\Blog;
use App\Models\User;
use App\Repositories\Contracts\BlogRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BlogService
{
    public function __construct(private readonly BlogRepositoryInterface $blogs) {}

    public function createPost(array $data, User $author): Blog
    {
        $payload = $data;
        $payload['author_id'] = $author->id;
        $payload['slug'] = $payload['slug'] ?? $this->generateSlug($payload['title'] ?? '');
        $payload['status'] = $payload['status'] ?? BlogStatus::Draft->value;
        $payload['title'] = $this->normalizeTranslatable($payload['title'] ?? '');
        $payload['content'] = $this->normalizeTranslatable($payload['content'] ?? '');
        if (! empty($payload['published_at']) && $payload['status'] === BlogStatus::Draft->value) {
            $payload['status'] = BlogStatus::Published->value;
        }

        return $this->blogs->create($payload);
    }

    public function updatePost(Blog $blog, array $data): Blog
    {
        $payload = $data;
        if (isset($payload['title']) && empty($payload['slug'])) {
            $payload['slug'] = $this->generateSlug($payload['title'], $blog->id);
        }
        if (array_key_exists('title', $payload)) {
            $payload['title'] = $this->normalizeTranslatable($payload['title']);
        }
        if (array_key_exists('content', $payload)) {
            $payload['content'] = $this->normalizeTranslatable($payload['content']);
        }
        if (! empty($payload['published_at']) && ($payload['status'] ?? $blog->status->value) === BlogStatus::Draft->value) {
            $payload['status'] = BlogStatus::Published->value;
        }

        $blog->update($payload);

        return $blog;
    }

    public function publish(Blog $blog, ?Carbon $when = null): Blog
    {
        $blog->forceFill([
            'published_at' => $when ?? Carbon::now(),
            'status' => BlogStatus::Published,
        ])->save();

        return $blog;
    }

    public function unpublish(Blog $blog): Blog
    {
        $blog->forceFill([
            'published_at' => null,
            'status' => BlogStatus::Draft,
        ])->save();

        return $blog;
    }

    public function generateSlug(string|array $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($this->extractTitle($title));
        if ($base === '') {
            $base = Str::random(8);
        }

        $slug = $base;
        $counter = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function queryForViewer(?User $viewer): Builder
    {
        $query = $this->blogs->query();

        if (auth('admin')->check()) {
            return $query;
        }

        if ($viewer) {
            return $query->where(function (Builder $builder) use ($viewer) {
                $builder->where('status', BlogStatus::Published)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', Carbon::now())
                    ->orWhere('author_id', $viewer->id);
            });
        }

        return $query->where('status', BlogStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', Carbon::now());
    }

    public function findVisibleBySlug(string $slug, ?User $viewer): ?Blog
    {
        return $this->queryForViewer($viewer)
            ->where('slug', $slug)
            ->first();
    }

    private function slugExists(string $slug, ?int $ignoreId): bool
    {
        $query = $this->blogs->query()->where('slug', $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    private function extractTitle($title): string
    {
        if (is_array($title)) {
            $locale = app()->getLocale();

            return $title[$locale] ?? $title[array_key_first($title)] ?? '';
        }

        return (string) $title;
    }

    private function normalizeTranslatable($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $locale = app()->getLocale();

        return [$locale => (string) $value];
    }
}
