<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private readonly ?Builder $query = null) {}

    public function query(): Builder
    {
        return ($this->query ?? User::query()->with('roles'))
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
            'Status',
            'Roles',
            'Locale',
            'Nationality',
            'Gender',
            'Created At',
        ];
    }

    /**
     * @param  User  $row
     * @return array<int, string|int|null>
     */
    public function map($row): array
    {
        return [
            $row->id,
            $row->name,
            $row->email,
            $row->phone ?? '',
            $row->status ?? '',
            $row->roles->pluck('name')->implode(', '),
            $row->locale ?? '',
            $row->nationality ?? '',
            $row->gender ?? '',
            $row->created_at?->format('Y-m-d H:i'),
        ];
    }
}
