<?php

namespace App\Repositories\Eloquent;

use App\Models\Page;
use App\Repositories\Contracts\PageRepositoryInterface;

class PageRepository implements PageRepositoryInterface
{
    public function findPublishedBySlug(string $slug): ?Page
    {
        return Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->first();
    }
}
