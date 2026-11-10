<?php

namespace App\Exports;

use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ServiceRequestsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly ?Builder $query = null) {}

    public function query(): Builder
    {
        return ($this->query ?? ServiceRequest::query()->with(['assignedTo']))
            ->reorder('id');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
            'Phone',
            'Source',
            'Status',
            'Priority',
            'Assigned To',
            'Message',
            'Created At',
        ];
    }

    /**
     * @param  ServiceRequest  $row
     * @return array<int, string|int|null>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->email ?? '',
            $row->phone ?? '',
            $row->source?->value ?? (string) $row->source,
            (string) $row->status,
            $row->priority?->value ?? (string) $row->priority,
            $row->assignedTo?->name ?? '',
            $row->message ?? '',
            $row->created_at?->format('Y-m-d H:i'),
        ];
    }
}
