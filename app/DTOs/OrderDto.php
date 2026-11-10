<?php

namespace App\DTOs;

class OrderDto
{
    public function __construct(
        public string $orderNo,
        public string $status,
        public int $totalAmount,
        public string $currency,
        public string $createdAt,
    ) {}
}
