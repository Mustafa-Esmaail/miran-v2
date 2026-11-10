<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\BankTransferStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * Amounts are in riyals, exactly as stored on the order.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customerName = $this->customer_name ?? $this->user?->name;
        [$firstName, $lastName] = app(GoogleSheetsService::class)->splitName((string) $customerName);
        $employee = $this->referredByEmployee;

        return [
            'order_no' => $this->order_no,
            'status' => $this->status?->value,
            'source' => $this->source?->value,
            'currency' => $this->currency,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'customer' => [
                'name' => $customerName,
                'first_name' => $firstName ?: null,
                'last_name' => $lastName ?: null,
                'email' => $this->user?->email,
                'phone' => $this->customer_phone,
                'phone_international' => $this->internationalPhone($this->customer_phone),
            ],
            'marketing' => [
                'code' => $employee?->employee_code ?? $this->marketing_source,
                'source' => $this->marketing_source,
                'team' => $employee?->team?->name,
                'employee' => $employee?->name,
                'employee_id' => $this->referred_by_employee_id,
                'employee_code' => $employee?->employee_code,
            ],
            'coupon' => $this->coupon ? [
                'code' => $this->coupon->code,
                'type' => $this->coupon->type?->value,
                'value' => $this->coupon->value,
            ] : null,
            'payment' => [
                'method' => $this->primary_payment_method,
                'paid_at' => $this->paidAt(),
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Site checkout stores Saudi mobiles as 9 digits starting with 5 ("5XXXXXXXX");
     * other formats (entered by sales/admin) are returned with only separators removed.
     */
    private function internationalPhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/[\s\-()]/', '', $phone);

        return match (true) {
            (bool) preg_match('/^5\d{8}$/', $digits) => '+966'.$digits,
            (bool) preg_match('/^05\d{8}$/', $digits) => '+966'.substr($digits, 1),
            (bool) preg_match('/^(00)?9665\d{8}$/', $digits) => '+'.ltrim($digits, '0'),
            default => $digits,
        };
    }

    /**
     * No dedicated paid-at column exists: use the last successful payment, otherwise
     * the approval time of the bank transfer receipt.
     */
    private function paidAt(): ?string
    {
        $paidPayment = $this->payments
            ->filter(fn (Payment $payment): bool => $payment->status === PaymentStatus::Paid)
            ->sortByDesc('updated_at')
            ->first();

        $paidAt = $paidPayment?->updated_at
            ?? ($this->bankTransferReceipt?->status === BankTransferStatus::Approved ? $this->bankTransferReceipt->reviewed_at : null);

        return $paidAt?->toIso8601String();
    }
}
