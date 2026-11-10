<?php

namespace App\Models;

use App\Enums\WorkflowTrigger;
use Illuminate\Database\Eloquent\Model;

class WorkflowRule extends Model
{
    protected $fillable = [
        'name',
        'trigger_event',
        'conditions',
        'actions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'trigger_event' => WorkflowTrigger::class,
            'conditions' => 'array',
            'actions' => 'array',
            'is_active' => 'bool',
        ];
    }
}
