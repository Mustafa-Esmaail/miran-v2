<?php

namespace App\Exports;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EnrollmentsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly ?Builder $query = null) {}

    public function query(): Builder
    {
        return ($this->query ?? Enrollment::query()->with(['user', 'course', 'courseGroup']))
            ->reorder('id');
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'User',
            'Email',
            'Course',
            'Course Group',
            'Status',
            'Invited',
            'Activated At',
            'Created At',
        ];
    }

    /**
     * @param  Enrollment  $row
     * @return array<int, string|int|float|null>
     */
    public function map($row): array
    {
        return [
            $row->user?->name ?? '',
            $row->user?->email ?? '',
            $row->course?->title ?? '',
            $row->courseGroup?->title ?? '',
            $row->status?->value ?? (string) $row->status,
            $row->is_invited ? __('admin.labels.yes') : __('admin.labels.no'),
            $row->activated_at?->format('Y-m-d H:i'),
            $row->created_at?->format('Y-m-d H:i'),
        ];
    }
}
