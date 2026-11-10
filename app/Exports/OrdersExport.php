<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class OrdersExport implements FromQuery, WithColumnFormatting, WithHeadings, WithMapping
{
    public function __construct(private readonly ?Builder $query = null) {}

    public function query(): Builder
    {
        return ($this->query ?? Order::query())
            ->with(['user', 'coupon', 'referredByEmployee', 'payments', 'bankTransferReceipt'])
            ->reorder('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Order No',
            'Customer',
            'Phone',
            'Email',
            'Status',
            'Subtotal',
            'Discount',
            'Total',
            'Currency',
            'Payment Method',
            'Coupon',
            'Employee Code',
            'Employee Name',
            'Notes',
            'Created At',
        ];
    }

    /**
     * @param  Order  $row
     * @return array<int, string|int|float|null>
     */
    public function map($row): array
    {
        return [
            $row->order_no,
            $row->user?->name ?? '',
            $row->customer_phone ?? $row->user?->phone ?? '',
            $row->user?->email ?? '',
            $row->status?->value ?? (string) $row->status,
            $row->subtotal_amount,
            $row->discount_amount,
            $row->total_amount,
            $row->currency,
            $row->primary_payment_method ?? '',
            $row->coupon?->code ?? '',
            $row->referredByEmployee?->employee_code ?? '',
            $row->referredByEmployee?->name ?? '',
            $row->notes ?? '',
            $row->created_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
        ];
    }
}
