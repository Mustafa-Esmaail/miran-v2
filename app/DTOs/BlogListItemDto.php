<?php

namespace App\DTOs;

class BlogListItemDto
{

    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $excerpt,
        public string $categoryName,
        public string $authorName,
        public ?string $publishedAt,
        public bool $isDraft,
        public int $commentsCount,
        public ?string $imageUrl = null,
    ) {}
}
