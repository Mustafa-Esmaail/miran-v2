<?php

namespace App\DTOs;

class CourseShowDto
{
    /** @param CourseGroupDto[] $groups */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $summary,
        public ?string $description,
        public string $slug,
        public ?string $thumbnailPath,
        public ?string $imageUrl,
        public ?float $price,
        public ?float $finalPrice,
        public ?int $durationHours,
        public ?int $daysCount,
        public ?string $durationGroup,
        public ?string $deliveryType,
        public string $categoryName,
        public array $groups,
        public array $trainers,
        public array $relatedCourses,
    ) {}
}
