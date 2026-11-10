<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    public function createPayment(array $data): Payment;

    public function findByProviderRef(string $provider, string $reference): ?Payment;

    public function sumPaidThisMonth(): int;
}
