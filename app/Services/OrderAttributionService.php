<?php

namespace App\Services;

use App\Enums\CommissionLedgerStatus;
use App\Enums\OrderAttributionType;
use App\Models\CommissionLedger;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderAttribution;
use App\Models\ServiceRequest;

class OrderAttributionService
{
    public function createForOrderIfEligible(Order $order): void
    {
        $serviceRequest = ServiceRequest::query()
            ->where('converted_order_id', $order->id)
            ->first();

        if (! $serviceRequest || ! $serviceRequest->assigned_to) {
            return;
        }

        $existing = OrderAttribution::query()
            ->where('order_id', $order->id)
            ->where('service_request_id', $serviceRequest->id)
            ->first();

        if ($existing) {
            return;
        }

        $attribution = OrderAttribution::query()->create([
            'order_id' => $order->id,
            'service_request_id' => $serviceRequest->id,
            'sales_user_id' => $serviceRequest->assigned_to,
            'attribution_type' => OrderAttributionType::Lead,
            'credited_amount' => $order->total_amount,
            'created_at' => now(),
        ]);

        $employee = Employee::query()->where('user_id', $serviceRequest->assigned_to)->first();

        if ($employee && $employee->commission_rate > 0) {
            $commissionAmount = (int) round($order->total_amount * ((float) $employee->commission_rate / 100));

            CommissionLedger::query()->create([
                'order_attribution_id' => $attribution->id,
                'employee_id' => $employee->id,
                'gross_amount' => $order->total_amount,
                'commission_rate' => $employee->commission_rate,
                'commission_amount' => $commissionAmount,
                'period' => now()->format('Y-m'),
                'status' => CommissionLedgerStatus::Pending,
                'created_at' => now(),
            ]);
        }
    }

    public function createForCouponIfEligible(Order $order): void
    {
        if (! $order->coupon_id) {
            return;
        }

        $coupon = $order->coupon ?? $order->load('coupon')->coupon;
        $coupon->loadMissing('employee');

        if (! $coupon->employee_id || ! $coupon->employee?->user_id) {
            return;
        }

        $exists = OrderAttribution::query()
            ->where('order_id', $order->id)
            ->where('attribution_type', OrderAttributionType::CouponCommission->value)
            ->exists();

        if ($exists) {
            return;
        }

        $employee = $coupon->employee;
        $commissionAmount = (int) round($order->total_amount * ((float) $employee->commission_rate / 100));

        $attribution = OrderAttribution::query()->create([
            'order_id' => $order->id,
            'service_request_id' => null,
            'sales_user_id' => $employee->user_id,
            'attribution_type' => OrderAttributionType::CouponCommission,
            'credited_amount' => $order->total_amount,
            'created_at' => now(),
        ]);

        CommissionLedger::query()->create([
            'order_attribution_id' => $attribution->id,
            'employee_id' => $employee->id,
            'gross_amount' => $order->total_amount,
            'commission_rate' => $employee->commission_rate,
            'commission_amount' => $commissionAmount,
            'period' => now()->format('Y-m'),
            'status' => CommissionLedgerStatus::Pending,
            'created_at' => now(),
        ]);
    }
}
