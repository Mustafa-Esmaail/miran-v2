<?php

namespace App\Repositories\Eloquent;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Carbon\Carbon;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function createPayment(array $data): Payment
    {
        return Payment::query()->create($data);
    }

    public function findByProviderRef(string $provider, string $reference): ?Payment
    {
        return Payment::query()->where('provider', $provider)->where('provider_reference', $reference)->first();
    }

    public function sumPaidThisMonth(): int
    {
        return $this->sumPaidBetween(now()->startOfMonth(), now()->endOfMonth());
    }

    private function sumPaidBetween(Carbon $start, Carbon $end): int
    {
        return (int) Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }
}
