<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly ?Builder $query = null) {}

    public function query(): Builder
    {
        return ($this->query ?? Payment::query()->with('order'))
            ->reorder('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Order No',
            'Provider',
            'Reference',
            'Status',
            'Amount',
            'Created At',
        ];
    }

    /**
     * @param  Payment  $row
     * @return array<int, string|int|float|null>
     */
    public function map($row): array
    {
        return [
            $row->order?->order_no ?? '',
            $row->provider,
            $row->provider_reference,
            $row->status?->value ?? (string) $row->status,
            $row->amount,
            $row->created_at?->format('Y-m-d H:i'),
        ];
    }
}
