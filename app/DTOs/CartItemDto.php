<?php

namespace App\DTOs;

class CartItemDto
{
    public function __construct(
        public int $id,
        public string $purchasableType,
        public int $purchasableId,
        public string $itemType,
        public string $title,
        public string $subtitle,
        public ?string $thumbnailUrl,
        public ?string $imageUrl,
        public int $priceAmount,
        public int $originalPriceAmount,
        public int $quantity,
        public ?int $courseId = null,
    ) {}
}
