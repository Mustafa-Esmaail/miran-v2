<?php

namespace App\Repositories\Contracts;

use App\Models\Page;

interface PageRepositoryInterface
{
    public function findPublishedBySlug(string $slug): ?Page;
}
