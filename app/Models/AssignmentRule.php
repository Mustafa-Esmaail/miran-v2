<?php

namespace App\Models;

use App\Enums\AssignmentStrategy;
use Illuminate\Database\Eloquent\Model;

class AssignmentRule extends Model
{
    protected $fillable = [
        'name',
        'conditions',
        'employee_ids',
        'strategy',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'conditions' => 'array',
            'employee_ids' => 'array',
            'strategy' => AssignmentStrategy::class,
            'priority' => 'integer',
            'is_active' => 'bool',
        ];
    }
}
