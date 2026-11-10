<?php

namespace App\DTOs;

class CourseGroupDto
{
    public function __construct(
        public int $id,
        public string $title,
        public ?int $priceAmount,
        public ?string $startAt,
        public ?string $endAt,
        public ?int $capacity,
        public ?string $enrollmentDeadlineAt,
        public string $status,
    ) {}
}
