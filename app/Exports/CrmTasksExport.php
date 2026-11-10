<?php

namespace App\Exports;

use App\Models\CrmTask;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CrmTasksExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly ?Builder $query = null) {}

    public function query(): Builder
    {
        return ($this->query ?? CrmTask::query()->with(['employee']))
            ->reorder('id');
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return [
            'ID',
            'Title',
            'Priority',
            'Status',
            'Entity Type',
            'Entity ID',
            'Employee',
            'Due At',
            'Is Done',
            'Description',
            'Created At',
        ];
    }

    /**
     * @param  CrmTask  $row
     * @return array<int, string|int|null>
     */
    public function map($row): array
    {
        $entityType = $row->entity_type;
        if ($entityType) {
            $parts = explode('\\', $entityType);
            $entityType = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', end($parts)) ?? '');
        }

        return [
            $row->id,
            $row->title,
            $row->priority?->value ?? $row->priority,
            $row->status?->value ?? (string) $row->status,
            $entityType ?? '',
            $row->entity_id,
            $row->employee?->name ?? '',
            $row->due_at?->format('Y-m-d H:i'),
            $row->is_done ? 'yes' : 'no',
            $row->description ?? '',
            $row->created_at?->format('Y-m-d H:i'),
        ];
    }
}
