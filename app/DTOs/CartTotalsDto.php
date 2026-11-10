<?php

namespace App\DTOs;

class CartTotalsDto
{
    public function __construct(
        public int $subtotalAmount,
        public int $discountAmount,
        public int $taxAmount,
        public int $totalAmount,
        public string $currency,
    ) {}
}
