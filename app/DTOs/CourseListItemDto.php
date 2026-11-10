<?php

namespace App\DTOs;

class CourseListItemDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public ?string $summary,
        public ?string $thumbnailPath,
        public string $categoryName,
    ) {}
}
